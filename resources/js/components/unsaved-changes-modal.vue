<template>
    <div v-if="open" class="modal">
        <div class="modal-overlay" @click="$emit('stay')"></div>
        <div class="modal-content max-w-lg rounded-sm overflow-hidden">
            <div class="inner p-6">
                <header class="modal-header p-4">
                    <h4 class="text-xl font-bold text-white">You have unsaved settings</h4>
                </header>
                <div class="modal-body px-4 pb-2">
                    <p class="text-gray-300">
                        Leaving this page now will discard the changes you have made.
                    </p>
                </div>
                <footer class="modal-footer flex flex-wrap justify-end items-center gap-3 p-4">
                    <button
                        type="button"
                        class="text-gray-300 px-3 py-2 rounded-sm hover:text-white"
                        @click.prevent="$emit('stay')"
                    >
                        Keep editing
                    </button>
                    <button
                        type="button"
                        class="text-white px-4 py-2 rounded-sm bg-gray-600 hover:bg-gray-500"
                        @click.prevent="$emit('discard')"
                    >
                        Discard changes
                    </button>
                    <button
                        type="button"
                        class="text-white px-4 py-2 rounded-sm bg-blue-600 hover:bg-blue-500"
                        :disabled="saving"
                        @click.prevent="$emit('save')"
                    >
                        {{ saving ? 'Saving…' : 'Save and leave' }}
                    </button>
                </footer>
            </div>
        </div>
    </div>
</template>

<script>
/**
 * Shown when a navigation is held back because the form has unsaved changes.
 * The three buttons are the three ways out; the parent's route guard is waiting
 * on whichever one is pressed.
 */
export default {
    name: 'UnsavedChangesModal',
    props: {
        open: { type: Boolean, default: false },
        saving: { type: Boolean, default: false },
    },
    emits: ['stay', 'discard', 'save'],
};
</script>
