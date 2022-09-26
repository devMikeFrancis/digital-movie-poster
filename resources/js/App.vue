<template>
    <div :class="{ rotated: rotated }">
        <router-view />
    </div>
</template>
<script>
export default {
    name: 'App',
    data: function () {
        return {
            rotated: false,
        };
    },
    created() {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('rotate') && urlParams.get('rotate') === 'true') {
            this.rotated = true;
        }
    },
};
</script>
<style></style>
