<template>
    <div class="modal sync-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content max-w-xl rounded-sm overflow-hidden">
            <div class="inner p-6">
                <header class="modal-header p-4 text-center">
                    <h4 class="text-2xl font-bold text-white">Syncing Posters</h4>
                </header>
                <div class="modal-body p-4">
                    <p class="mb-5 text-white">
                        Feel free to close this and go about your business. The posters will sync in
                        the background and load in batches. Refresh this page later.
                    </p>
                    <p class="text-white mb-5">
                        This process may take a while if you have 1000s of movies. Once the sync
                        process is complete, click the
                        <strong>Refresh Movie Posters</strong> button.
                    </p>
                </div>
                <footer class="modal-footer flex justify-center items-center p-4">
                    <button
                        type="button"
                        class="bg-white text-black px-6 py-2 rounded-sm"
                        @click.prevent="$emit('close')"
                    >
                        OK
                    </button>
                </footer>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function () {
        return {};
    },
};
</script>
