<template>
    <div class="mb-5">
        <label class="text-gray-300 block mb-2 font-bold" for="title-search">
            Find a title
        </label>

        <div class="flex gap-2">
            <input
                id="title-search"
                v-model="term"
                type="text"
                class="text-black w-full"
                placeholder="Search by name, e.g. Blade Runner"
                @keydown.enter.prevent="search"
            />
            <button
                type="button"
                class="btn text-black bg-gray-300 px-4 rounded-sm hover:bg-gray-100 whitespace-nowrap"
                :disabled="busy"
                @click.prevent="search"
            >
                {{ busy ? 'Searching…' : 'Search' }}
            </button>
        </div>
        <div class="text-gray-400 text-sm mt-1">
            Searches TMDB, which is where the poster data comes from. Picking a result fills in
            the IMDB ID and the rest of the fields below.
        </div>

        <p v-if="message" class="text-sm mt-2" :class="messageClass">{{ message }}</p>

        <ul v-if="results.length" class="mt-3 max-h-96 overflow-y-auto rounded-sm">
            <li
                v-for="result in results"
                :key="result.tmdb_id"
                class="flex items-center gap-3 p-2 cursor-pointer border-b border-gray-700"
                :class="selected && selected.tmdb_id === result.tmdb_id ? 'bg-blue-900' : 'hover:bg-gray-800'"
                @click="selected = result"
            >
                <img
                    v-if="result.thumbnail"
                    :src="result.thumbnail"
                    :alt="result.title"
                    width="46"
                    height="69"
                    class="shrink-0 rounded-sm"
                    loading="lazy"
                />
                <span
                    v-else
                    class="shrink-0 flex items-center justify-center text-gray-500 text-xs bg-gray-800 rounded-sm"
                    style="width: 46px; height: 69px"
                >
                    No art
                </span>

                <span class="grow">
                    <span class="text-white block">{{ result.title }}</span>
                    <span class="text-gray-400 text-sm">
                        {{ result.year || 'Year unknown' }} ·
                        {{ result.media_type === 'tv' ? 'TV' : 'Movie' }}
                    </span>
                </span>

                <span v-if="selected && selected.tmdb_id === result.tmdb_id" class="text-white pr-2">
                    Selected
                </span>
            </li>
        </ul>

        <div v-if="selected" class="mt-3 flex items-center gap-3">
            <button
                type="button"
                class="btn text-white px-4 py-1 rounded-sm"
                style="background-color: #2563eb"
                :disabled="busy"
                @click.prevent="confirm"
            >
                {{ busy ? 'Fetching…' : 'Use “' + selected.title + '”' }}
            </button>
            <button
                type="button"
                class="btn text-gray-300 px-3 py-1 rounded-sm hover:text-white"
                @click.prevent="reset"
            >
                Cancel
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TitleLookup',
    props: {
        mediaType: {
            type: String,
            default: 'movie',
        },
    },
    emits: ['selected'],
    data() {
        return {
            term: '',
            results: [],
            selected: null,
            busy: false,
            message: '',
            messageIsError: false,
        };
    },
    computed: {
        messageClass() {
            return this.messageIsError ? 'text-red-400' : 'text-gray-400';
        },
    },
    methods: {
        reset() {
            this.results = [];
            this.selected = null;
            this.message = '';
            this.messageIsError = false;
        },
        say(text, isError = false) {
            this.message = text;
            this.messageIsError = isError;
        },
        search() {
            if (this.term.trim().length < 2) {
                this.say('Type at least two characters to search.', true);
                return;
            }

            this.busy = true;
            this.reset();

            axios
                .get('/api/tmdb/search', {
                    params: { query: this.term.trim(), media_type: this.mediaType },
                })
                .then(({ data }) => {
                    this.results = data.results;
                    if (this.results.length === 0) {
                        this.say('Nothing matched that title.');
                    }
                })
                .catch((error) => this.say(this.readError(error), true))
                .finally(() => {
                    this.busy = false;
                });
        },
        confirm() {
            if (!this.selected) {
                return;
            }

            this.busy = true;

            axios
                .get('/api/tmdb/title', {
                    params: {
                        tmdb_id: this.selected.tmdb_id,
                        media_type: this.selected.media_type,
                    },
                })
                .then(({ data }) => {
                    this.$emit('selected', data.title);
                    this.term = '';
                    this.reset();
                })
                .catch((error) => this.say(this.readError(error), true))
                .finally(() => {
                    this.busy = false;
                });
        },
        readError(error) {
            const data = error.response && error.response.data;
            return (data && data.message) || 'The lookup failed. Please try again.';
        },
    },
};
</script>
