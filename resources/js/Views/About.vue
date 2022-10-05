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
                                    <p class="text-white">{{ updateOutput }}</p>
                                </div>

                                <p class="text-white">
                                    This project is maintained by Don Jones at
                                    <a
                                        class="text-gray-400 hover:text-white"
                                        href="https://github.com/newelement/digital-movie-poster"
                                        target="_blank"
                                        >https://github.com/newelement/digital-movie-poster</a
                                    >
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
                        .get(
                            'https://raw.githubusercontent.com/newelement/digital-movie-poster/main/public/version.json'
                        )
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
                .catch((e) => {
                    console.log(e.message);
                    this.updateBtnLabel = this.origUpdateBtnLabel;
                    e.disabled = false;
                });
        },
        processVersion() {
            let currentVersion = parseInt(this.localVersion.replace('.', ''));
            let remoteVersion = 16900; //parseInt(this.remoteVersion.replace('.', ''));

            if (remoteVersion > currentVersion) {
                this.updateAvailable = true;
            }
        },
    },
    created() {},
    mounted() {
        this.checkVersion();
    },
};
</script>

<style lang="scss"></style>
