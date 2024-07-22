import axios from "axios";
import ToastifyEs from "toastify-js/src/toastify-es.js";

const httpClient = axios.create({
    baseURL: import.meta.env.VITE_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
    }
});

httpClient.interceptors.response.use(
    (response) => {
        ToastifyEs({
            text: "Fetching/ Updating data successful!",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: {
                background: "green",
            },
        }).showToast();
        return response.data
    },
    (error) => {
        const statusCode = error.response.status;
        let message = "";
        switch (statusCode) {
            case 500:
                message = `${error.response.data.message} , Please refresh your browser or try again`
                break;

            case 400:
                console.log(typeof error.response.data.errors)
                message = Object.values(error.response.data.errors)
                    .flat()
                    .join(', ');
                break;
        }

        ToastifyEs({
            text: message,
            duration: 5000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: {
                background: "red",
            },
        }).showToast();

        throw error.response.data;
    })

export default {
    getRockets() {
        return httpClient.get("/rockets");
    },

    deployRocket(rocketId) {
        return httpClient.put(`/rockets/${rocketId}/status/deployed`)
    },

    launchRocket(rocketId) {
        return httpClient.put(`/rockets/${rocketId}/status/launched`)
    },

    cancelRocket(rocketId) {
        return httpClient.delete(`/rockets/${rocketId}/status/launched`)
    },

    getWeather() {
        return httpClient.get("/weather");
    }
}
