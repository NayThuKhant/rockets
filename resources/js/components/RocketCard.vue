<template>
    <div class="bg-white shadow-lg rounded-lg p-6 mx-auto my-2 hover:bg-gray-100">
        <h2 class="text-lg font-bold text-blue-600 mb-4 flex items-center">
            <img class="h-4 w-4 mr-3" src="/images/rocket.svg" alt="">
            {{ rocket.model }} - {{ rocket.id }} (MASS {{ rocket.mass }} kg)
        </h2>

        <div class="text-sm flex justify-between">
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Status</span>
                <span class="uppercase font-bold underline">{{ rocket.status }}</span>
            </div>
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Altitude</span>
                <span>{{ rocket.altitude }} m</span>
            </div>
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Speed</span>
                <span>{{ rocket.speed }} m/s</span>
            </div>
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Acceleration</span>
                <span>{{ rocket.acceleration }} m/s<sup>2</sup></span>
            </div>
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Thrust</span>
                <span>{{ rocket.thrust }} N</span>
            </div>
            <div class="flex-1 flex flex-col">
                <span class="font-semibold">Temperature</span>
                <span>{{ rocket.temperature }} °C</span>
            </div>
        </div>

        <div class="text-xs flex flex-col mt-3">
            <span class="font-semibold">Payload</span>
            <span>Description : {{ rocket.payload.description }} </span>
            <span>Weight {{ rocket.payload.weight }} kg</span>
        </div>

        <div class="text-xs flex my-3">
            <div class="flex-1">
                <div class="font-semibold">LAUNCHED</div>
                <div>{{ rocket.timestamps.launched ?? "_" }}</div>
            </div>
            <div class="flex-1">
                <div class="font-semibold">DEPLOYED</div>
                <div>{{ rocket.timestamps.deployed ?? "_" }}</div>
            </div>
            <div class="flex-1">
                <div class="font-semibold">FAILED</div>
                <div>{{ rocket.timestamps.failed ?? "_" }}</div>
            </div>
            <div class="flex-1">
                <div class="font-semibold">CANCELLED</div>
                <div>{{ rocket.timestamps.cancelled ?? "_" }}</div>
            </div>
        </div>
        <div v-if="loading" class="text-xs">Loading ...</div>
        <div v-else>
            <button v-if="rocket.status === 'waiting'"
                    @click="deployRocket(rocket.id)"
                    class="bg-blue-500 text-white text-xs font-bold py-2 px-4 rounded focus:ring-blue-500">
                DEPLOY
            </button>
            <button v-else-if="rocket.status === 'deployed' || rocket.status === 'cancelled'"
                    @click="launchRocket(rocket.id)"
                    class="bg-green-500 text-white text-xs font-bold py-2 px-4 rounded focus:ring-blue-500">
                LAUNCH
            </button>
            <button v-else-if="rocket.status === 'launched'"
                    @click="cancelRocket(rocket.id)"
                    class="bg-red-500 text-white text-xs font-bold py-2 px-4 rounded focus:ring-blue-500">
                CANCEL
            </button>
        </div>
        <div v-if="connection.status" class="text-xs bg-green-300 rounded-3xl p-1 mt-4 font-bold inline-block">Connected !</div>
        <div v-else class="text-xs bg-red-300 rounded-3xl p-1 mt-4 font-bold inline-block">Disconnected !, Please refresh your browser</div>
        <div class="text-xs text-red-500 font-weight-lighter mt-2">Last Updated at {{ rocket.last_updated }} (UTC)</div>
    </div>
</template>

<script setup>
import {onMounted, onUnmounted, ref, unref} from 'vue';
import httpClient from "../httpClient.js";

const rocket = ref();
const loading = ref(false);
const connection = ref({
    status: true,
    lastUpdatedAt: new Date().getTime()
})
const intervalId = ref();

const props = defineProps({
    rocket: {
        type: Object,
        required: true,
    }
});
rocket.value = unref(props.rocket)

const deployRocket = async (rocketId) => {
    try {
        loading.value = true
        const updatedRocket = await httpClient.deployRocket(rocketId)
        if (updatedRocket) rocket.value = updatedRocket
    } catch (err) {
        console.error('Error deploying rocket:', err);
    }
    loading.value = false
}

const launchRocket = async (rocketId) => {
    try {
        loading.value = true
        const updatedRocket = await httpClient.launchRocket(rocketId)
        if (updatedRocket) rocket.value = updatedRocket
    } catch (err) {
        console.error('Error launching rocket:', err);
    }
    loading.value = false
}

const cancelRocket = async (rocketId) => {
    try {
        loading.value = true
        const updatedRocket = await httpClient.cancelRocket(rocketId)
        if (updatedRocket) rocket.value = updatedRocket
    } catch (err) {
        console.error('Error cancelling rocket:', err);
    }
    loading.value = false
}

const listenRocketUpdatedInformation = () => {
    window.Echo.channel('rocket')
        .listen(`.${rocket.value.id}`, (updatedRocket) => {
            rocket.value.speed = updatedRocket.speed
            rocket.value.acceleration = updatedRocket.acceleration
            rocket.value.altitude = updatedRocket.altitude
            rocket.value.thrust = updatedRocket.thrust
            rocket.value.temperature = updatedRocket.temperature
            rocket.value.last_updated = updatedRocket.last_updated
            if (updatedRocket.status && updatedRocket.status !== rocket.value.status) rocket.value.status = updatedRocket.status

            connection.value.status = true
            connection.value.lastUpdatedAt = new Date().getTime()
        });
}

onMounted(() => {
    listenRocketUpdatedInformation()
    intervalId.value = setInterval(() => {
        if (new Date().getTime() - connection.value.lastUpdatedAt > 1000) {
            connection.value.status = false
        }
    }, 1000)
})

onUnmounted(() => {
    clearInterval(intervalId.value)
})
</script>
