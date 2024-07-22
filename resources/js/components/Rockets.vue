<template>
    <div class="mx-auto px-4 py-8">
        <Weather/>
        <div style="padding-top: 150px" class="text-sm flex items-center" v-if="fetchRocketsStatus">
            <span>{{ fetchRocketsStatus }}</span>
            <button
                v-if="fetchRocketError"
                class="ml-4 bg-blue-500 text-white text-xs font-bold py-2 px-4 rounded focus:ring-blue-500 flex items-center"
                @click="fetchRockets">
                <img class="h-4 w-4 mr-4" src="/images/retry.png" alt="Retry" @click="fetchRockets"> Retry
            </button>
        </div>
        <div v-else style="padding-top: 150px">
            <RocketCard
                v-for="rocket in rockets"
                :rocket="rocket"
            />
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted, onUnmounted} from 'vue';
import RocketCard from './RocketCard.vue';
import Weather from "./Weather.vue";
import httpClient from "../httpClient.js";

const rockets = ref([]);
const fetchRocketsStatus = ref();
const fetchRocketError = ref();

const fetchRockets = async () => {
    fetchRocketsStatus.value = "Loading rockets ..."
    fetchRocketError.value = false;
    try {
        rockets.value = await httpClient.getRockets();
        fetchRocketsStatus.value = "";
    } catch (err) {
        console.error('Error fetching rockets:', err);
        fetchRocketsStatus.value = "Error fetching rockets, please try again or your browser ...";
        fetchRocketError.value = true
    }
};

onMounted(async () => {
    await fetchRockets();
});

onUnmounted(() => {
    // Leave all channel once the component is unmounted
    window.Echo.leaveAllChannels();
});
</script>
