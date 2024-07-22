<template>
    <div class="weather">
        <div class="text-sm flex items-center p-4" v-if="fetchWeatherStatus">
            <span>{{ fetchWeatherStatus }}</span>
            <button
                v-if="fetchWeatherError"
                class="ml-4 bg-blue-500 text-white text-xs font-bold py-2 px-4 rounded focus:ring-blue-500 flex items-center"
                @click="fetchWeather">
                <img class="h-4 w-4 mr-4" src="/images/retry.png" alt="Retry"> Retry
            </button>
        </div>
        <div v-else class="flex flex-col p-4 text-xs">
            <div class="flex">
                <div class="flex-1">
                    <div class="font-semibold">Temperature</div>
                    <div>{{ weather?.temperature ?? "_" }} °C</div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold">Humidity</div>
                    <div>{{ weather?.humidity ?? "_" }} %</div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold">Pressure</div>
                    <div>{{ weather?.pressure ?? "_" }} hPa</div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold">Precipitation</div>
                    <div class="flex flex-col">Probability: {{ weather?.precipitation.probability ?? "_" }} %</div>
                    <div class="flex flex-col">Rain: {{ weather?.precipitation.rain ?? "_" }}</div>
                    <div class="flex flex-col">Snow: {{ weather?.precipitation.snow ?? "_" }}</div>
                    <div class="flex flex-col">Sleet: {{ weather?.precipitation.sleet ?? "_" }}</div>
                    <div class="flex flex-col">Hail: {{ weather?.precipitation.hail ?? "_" }}</div>
                </div>
                <div class="flex-1">
                    <div class="font-semibold">Wind</div>
                    <div class="flex flex-col">Direction: {{ weather?.wind.direction ?? "_" }}</div>
                    <div class="flex flex-col">Angle: {{ weather?.wind.angle ?? "_" }} °</div>
                    <div class="flex flex-col">Speed: {{ weather?.wind.speed ?? "_" }} m/s</div>
                </div>
            </div>

            <div class="text-xs text-red-500 font-weight-lighter mt-2">Last Updated at {{ weather?.time ?? "_" }} (UTC)
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from "vue";
import httpClient from "../httpClient.js";

const weather = ref();
const fetchWeatherStatus = ref('');
const fetchWeatherError = ref(false);

const fetchWeather = async () => {
    fetchWeatherStatus.value = "Loading weather ...";
    fetchWeatherError.value = false;
    try {
        weather.value = await httpClient.getWeather();
        fetchWeatherStatus.value = "";
    } catch (err) {
        console.error('Error fetching weather:', err);
        fetchWeatherStatus.value = "Error fetching weather, please try again or check your browser ...";
        fetchWeatherError.value = true;
    }
};

const listenRocketUpdatedInformation = () => {
    window.Echo.channel('weather')
        .listen(`.updated`, (updatedWeather) => {
            weather.value = updatedWeather;
        });
}

onMounted(async () => {
    await fetchWeather();
    listenRocketUpdatedInformation();
});
</script>

<style scoped>
.weather {
    width: 100%;
    height: 150px;
    background: #5bdf37;
    position: fixed;
    top: 0;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    z-index: 1000;
}
</style>
