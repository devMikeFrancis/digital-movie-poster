<template>
    <div class="settings-bar">
        <ul class="tabs">
            <li v-for="item in tabs" :key="item.id">
                <a
                    :href="'?tab=' + item.id"
                    class="text-sm md:text-md"
                    :class="{ active: item.id === modelValue }"
                    @click.prevent="$emit('update:modelValue', item.id)"
                    >{{ item.label }}</a
                >
            </li>
        </ul>

        <div class="settings-bar-actions">
            <span class="text-sm" :class="statusClass">{{ statusText }}</span>
            <button
                type="submit"
                class="btn text-md px-4 py-1 rounded-sm whitespace-nowrap"
                :class="saveButtonClass"
                :disabled="saving || !unsavedChanges"
                @click.prevent="$emit('save')"
            >
                {{ saving ? 'Saving…' : 'Save settings' }}
            </button>
        </div>
    </div>
</template>

<script>
/**
 * Tabs on the left, save on the right, stuck to the top.
 *
 * Both settings screens carry one of these. The tab links are real hrefs so
 * that the open tab can be copied out of the address bar, and are intercepted
 * so that switching tabs does not reload the page.
 */
export default {
    name: 'SettingsBar',
    props: {
        /** @type {Array<{ id: string, label: string }>} */
        tabs: { type: Array, required: true },
        modelValue: { type: String, default: '' },
        statusText: { type: String, default: '' },
        statusClass: { type: String, default: '' },
        saving: { type: Boolean, default: false },
        unsavedChanges: { type: Boolean, default: false },
    },
    emits: ['update:modelValue', 'save'],
    computed: {
        saveButtonClass() {
            return this.unsavedChanges && !this.saving
                ? 'text-white bg-blue-600 hover:bg-blue-500'
                : 'text-gray-400 bg-gray-700 cursor-default';
        },
    },
};
</script>

<style scoped lang="scss">
/*
 * The form is long enough that a button at the bottom was easy to miss, and
 * easy to forget after scrolling back up.
 */
.settings-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 20;
    background-color: #121212;
    padding: 8px 0;
    margin-bottom: 4px;
}

.settings-bar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 4px;
}

.settings-bar-actions button:disabled {
    opacity: 0.75;
}

/* The look the tabs have always had; only the wiring behind them changed. */
.tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    a {
        display: block;
        padding: 8px 12px;
        min-width: 112px;
        text-align: center;
        color: #888;
        background-color: #333;

        &:hover {
            background-color: #777;
            color: #ccc;
            transition: background-color 0.25s ease;
        }

        &.active {
            color: #fff;
            background-color: #555;

            &:hover {
                background-color: #777;
                transition: background-color 0.25s ease;
            }
        }
    }
}
</style>
