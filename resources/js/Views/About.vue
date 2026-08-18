<template>
    <div>
        <div class="admin py-5">
            <div class="md:container md:mx-auto lg:container lg:mx-auto">
                <div class="grid lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-3">
                        <main-nav />
                    </div>
                    <div class="lg:col-span-9 p-4 relative" style="background-color: #121212">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="md:col-span-12">
                                <h2 class="mb-8 text-white font-bold text-3xl">About</h2>

                                <p class="mb-5 text-white">
                                    <strong>App Version:</strong> {{ localVersion }}
                                    <span
                                        class="
                                            bg-gray-600
                                            text-white
                                            px-2
                                            py-1
                                            ml-2
                                            rounded-xl
                                            text-sm
                                        "
                                        v-if="!updateAvailable"
                                        >Up to date</span
                                    >
                                </p>

                                <div v-if="updateAvailable" class="mb-8">
                                    <p class="mb-3 text-lg text-white font-bold">
                                        There is a new update available - {{ remoteVersion }}
                                    </p>
                                    <p>
                                        <button
                                            class="
                                                bg-white
                                                rounded-md
                                                text-black
                                                mb-3
                                                px-3
                                                py-2
                                                text-sm
                                                hover:bg-gray-400
                                            "
                                            @click="updateApplication($event)"
                                        >
                                            {{ updateBtnLabel }}
                                        </button>
                                    </p>
                                    <p class="text-white" style="white-space: pre-wrap">{{ updateOutput }}</p>
                                </div>

                                <p class="text-white">
                                    This project is maintained by Mike Francis at
                                    <a
                                        class="text-gray-400 hover:text-white"
                                        href="https://github.com/devMikeFrancis/digital-movie-poster"
                                        target="_blank"
                                        >https://github.com/devMikeFrancis/digital-movie-poster</a
                                    >
                                </p>
                                <p class="text-gray-400 text-sm mt-2">
                                    Originally created by Don Jones.
                                </p>

                                <div class="pt-12">
                                    <p class="text-white"><a href="/log-viewer">View Logs</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { mapState } from 'pinia';
import { usePostersStore } from '@/store/posters';
import MainNav from '@/partials/MainNav.vue';

export default {
    data: function () {
        return {
            localVersion: '',
            remoteVersion: '',
            updateAvailable: false,
            updateBtnLabel: 'Click here to update',
            origUpdateBtnLabel: 'Click here to update',
            updatingBtnLabel: 'Updating ...',
            updateOutput: '',
        };
    },
    components: { MainNav },
    computed: {
        ...mapState(usePostersStore, ['settings']),
    },
    methods: {
        checkVersion() {
            axios
                .get('/version.json')
                .then((response) => {
                    this.localVersion = response.data.latest;

                    axios
                        .get('/api/check-update')
                        .then((response) => {
                            this.remoteVersion = response.data.latest;
                            this.processVersion(response);
                        })
                        .catch((e) => {
                            console.log(e.message);
                        });
                })
                .catch((e) => {
                    console.log(e.message);
                });
        },
        updateApplication(e) {
            e.disabled = true;
            this.updateOutput = '';
            this.updateBtnLabel = this.updatingBtnLabel;
            axios
                .get('/api/update-application')
                .then((response) => {
                    this.updateOutput = 'Update complete. Reloading in 5 seconds.';
                    this.updateBtnLabel = this.origUpdateBtnLabel;
                    setTimeout(() => {
                        location.reload();
                    }, 5000);
                })
                .catch((error) => {
                    // The update script explains why it stopped - for instance
                    // that this device's PHP is too old and the installer has
                    // to be re-run. Show that rather than swallowing it.
                    const data = error.response && error.response.data;
                    this.updateOutput =
                        (data && (data.output || data.message)) ||
                        'The update could not be started.';
                    this.updateBtnLabel = this.origUpdateBtnLabel;
                    e.disabled = false;
                });
        },
        /**
         * Compare two dotted version strings.
         *
         * The previous implementation stripped the dots and compared the
         * result as an integer, so "2.0.0" (200) looked older than "1.7.153"
         * (17153) and no update was ever offered across a major version.
         *
         * @returns {number} positive when a is newer than b
         */
        compareVersions(a, b) {
            const parse = (v) =>
                String(v || '')
                    .split('.')
                    .map((part) => parseInt(part, 10) || 0);

            const left = parse(a);
            const right = parse(b);
            const length = Math.max(left.length, right.length);

            for (let i = 0; i < length; i++) {
                const diff = (left[i] || 0) - (right[i] || 0);
                if (diff !== 0) {
                    return diff;
                }
            }

            return 0;
        },
        processVersion() {
            this.updateAvailable = this.compareVersions(this.remoteVersion, this.localVersion) > 0;
        },
    },
    created() {},
    mounted() {
        this.checkVersion();
    },
};
</script>

<style lang="scss"></style>
