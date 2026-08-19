import axios from 'axios';

/**
 * The settings form, shared by the two screens that edit it.
 *
 * Display and Settings edit different halves of the same settings row, so both
 * need the same handling: load the row, notice when it differs from what is
 * stored, save it, and refuse to be navigated away from with something unsaved.
 * None of that is worth writing twice, and writing it twice is how the two
 * halves would drift apart.
 *
 * The component supplies `tabs` - a list of { id, label } - and declares its
 * own beforeRouteLeave, because vue-router reads in-component guards from the
 * component's own options and does not see one arriving through a mixin.
 */
export default {
    data() {
        return {
            settings: {
                require_login: true,
                poster_fill_screen: false,
                poster_fill_scrim: 'standard',
                show_header_text: true,
                show_theater_name: false,
                theater_name: '',
                theater_name_position: 'bottom',
                theater_name_style: 'plain',
                theater_name_full_width: false,
                header_style: 'plain',
                header_position: 'top',
                header_full_width: false,
                mpaa_limit: '',
                tv_limit: '',
                plex_token: '',
                plex_ip_address: '',
                jellyfin_token: '',
                jellyfin_ip_address: '',
                transition_type: 'fade',
            },
            // JSON of the settings as last loaded or saved. Anything different
            // from this is an unsaved change.
            savedSnapshot: '',
            saving: false,
            saveFailed: false,
            justSaved: false,
            settingsMessage: '',
            errors: [],
            // The navigation held back while the unsaved-changes prompt is up.
            pendingLeave: null,
            tab: '',
        };
    },
    computed: {
        /**
         * Whether the form differs from what is stored.
         *
         * The form is long enough that the old button at the very bottom was
         * easy to miss, so the header bar has to say plainly whether anything
         * is waiting to be saved.
         */
        unsavedChanges() {
            return (
                this.savedSnapshot !== '' && JSON.stringify(this.settings) !== this.savedSnapshot
            );
        },
        statusText() {
            if (this.saving) {
                return 'Saving…';
            }
            if (this.saveFailed) {
                return 'Not saved';
            }
            if (this.unsavedChanges) {
                return 'Unsaved changes';
            }
            if (this.justSaved) {
                return 'Saved';
            }

            return '';
        },
        statusClass() {
            if (this.saveFailed) {
                return 'text-red-400';
            }
            if (this.unsavedChanges) {
                return 'text-amber-300';
            }

            return 'text-green-400';
        },
        /**
         * Laravel's 422 body repeats the first field error in "message", so
         * showing both put the same sentence on screen twice.
         */
        errorHeading() {
            if (this.errors.length) {
                return this.errors.length === 1
                    ? 'That setting could not be saved:'
                    : 'Those settings could not be saved:';
            }

            return this.settingsMessage || 'Those settings could not be saved.';
        },
    },
    mounted() {
        this.tab = this.tabFromRoute();
        this.getSettings();
        window.addEventListener('beforeunload', this.warnBeforeUnload);
    },
    beforeUnmount() {
        window.removeEventListener('beforeunload', this.warnBeforeUnload);
    },
    methods: {
        /**
         * The open tab is in the URL so that it survives a reload and can be
         * linked to. It used to be a class the click handler put on a div,
         * which meant every reload dropped you back on the first tab - most
         * annoying on the one you go back to repeatedly.
         */
        tabFromRoute() {
            const wanted = this.$route.query.tab;

            return this.tabs.some((item) => item.id === wanted) ? wanted : this.tabs[0].id;
        },
        setTab(id) {
            this.tab = id;
            this.$router.replace({ query: { ...this.$route.query, tab: id } });
        },
        getSettings() {
            return axios
                .get('/api/settings/full')
                .then((response) => {
                    this.settings = this.withSelectDefaults(response.data);
                    this.markClean();
                    this.settingsLoaded();
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        /** Overridden by a screen that has more to fetch once it knows what is set. */
        settingsLoaded() {},
        markClean() {
            this.savedSnapshot = JSON.stringify(this.settings);
        },
        saveSettings() {
            if (this.saving || !this.unsavedChanges) {
                return Promise.resolve();
            }

            this.settingsMessage = '';
            this.errors = [];
            this.saveFailed = false;
            this.justSaved = false;
            this.saving = true;

            // Sent alongside rather than written onto this.settings, which
            // would otherwise register as an unsaved change of its own.
            return axios
                .post('/api/settings', { ...this.settings, _method: 'put' })
                .then(() => {
                    this.markClean();
                    this.justSaved = true;
                    setTimeout(() => {
                        this.justSaved = false;
                    }, 4000);
                })
                .catch((e) => {
                    this.saveFailed = true;
                    const response = e.response;
                    this.settingsMessage =
                        (response && response.data && response.data.message) || e.message;

                    const errors = (response && response.data && response.data.errors) || {};
                    Object.keys(errors).forEach((field) => {
                        if (errors[field] instanceof Array) {
                            errors[field].forEach((err) => this.errors.push(err));
                        }
                    });
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        /**
         * A select bound to null matches no option, not the one whose value is
         * the empty string - so "None" rendered blank on any install that had
         * never set a rating limit, and the field looked broken. Nothing here
         * changes what is stored; null and '' both mean no limit.
         */
        withSelectDefaults(settings) {
            const emptyIsAChoice = ['mpaa_limit', 'tv_limit'];

            emptyIsAChoice.forEach((key) => {
                if (settings[key] === null || settings[key] === undefined) {
                    settings[key] = '';
                }
            });

            // Same trap as the rating limits: a select bound to null renders
            // blank rather than showing the option it is really on.
            const fallbacks = {
                theater_name_position: 'bottom',
                theater_name_style: 'plain',
                header_style: 'plain',
                header_position: 'top',
                poster_fill_scrim: 'standard',
                transition_type: 'fade',
            };

            Object.keys(fallbacks).forEach((key) => {
                if (!settings[key]) {
                    settings[key] = fallbacks[key];
                }
            });

            return settings;
        },
        /**
         * Hold back in-app navigation while there are unsaved settings and let
         * the prompt decide. Switching tabs is a route change of its own now,
         * so a move within the same screen has to pass straight through.
         */
        confirmLeave(to, from, next) {
            if (to.path === from.path || !this.unsavedChanges) {
                next();

                return;
            }

            this.pendingLeave = (proceed = true) => next(proceed === false ? false : undefined);
        },
        stayOnPage() {
            if (this.pendingLeave) {
                this.pendingLeave(false);
                this.pendingLeave = null;
            }
        },
        leaveWithoutSaving() {
            if (this.pendingLeave) {
                const proceed = this.pendingLeave;
                this.pendingLeave = null;
                this.markClean(); // so the beforeunload handler does not fire too
                proceed();
            }
        },
        saveThenLeave() {
            this.saveSettings().then(() => {
                if (this.saveFailed) {
                    // Cancel the navigation and step out of the way: the
                    // reason it failed is in the banner behind this dialog,
                    // and leaving the dialog up just invites another attempt.
                    this.stayOnPage();

                    return;
                }

                this.leaveWithoutSaving();
            });
        },
        /**
         * Covers leaving the app entirely - reload, tab close, typed URL. The
         * router guard cannot see those.
         */
        warnBeforeUnload(event) {
            if (!this.unsavedChanges) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        },
    },
};
