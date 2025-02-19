<script setup>
import BreezeAuthenticatedLayout from "@/Layouts/Authenticated.vue";
import { Head } from "@inertiajs/inertia-vue3";
import BreezeButton from "@/Components/Button.vue";
import Pagination from "@/Components/Pagination.vue";
import SortArrowUp from "@/Components/SortArrowUp.vue";
import SortArrowDown from "@/Components/SortArrowDown.vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
// import { useDateFormat, useNow } from '@vueuse/core'
import { usePage } from '@inertiajs/inertia-vue3';
import { ref, watch, reactive, onMounted } from 'vue';


// Props from parent component
const props = defineProps({
  weather: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  }
});

// Reactive variables
const city = ref('');  // Declare city as a ref
const weatherData = ref(null);
const forecastData = ref(null);
const calculationResults = ref(null);
const errorMessage = ref(null);
const localTime = ref('');  // Declare reactive state for local time

// Utility function to get current formatted system time
function getCurrentTime() {
  const now = new Date();
  const options = {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  };
  return new Intl.DateTimeFormat('en-GB', options).format(now);
}

// Format date utility function
function formatDate(dateString) {
  const date = new Date(dateString);
  
  // Add ordinal suffix to day
  const day = date.getDate();
  const suffix = getDaySuffix(day);
  
  // Get month abbreviation
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const month = months[date.getMonth()];
  
  // Format full date
  return `${day}${suffix} ${month} ${date.getFullYear()}`;
}

// Helper function to get day suffix (th, st, nd, rd)
function getDaySuffix(day) {
  if (day > 3 && day < 21) return 'th';
  switch (day % 10) {
    case 1: return 'st';
    case 2: return 'nd';
    case 3: return 'rd';
    default: return 'th';
  }
}

// Watch for route changes
watch(
  () => usePage().url.value,
  (newUrl) => {
    Inertia.reload({ preserveScroll: true });
  }
);

// Watch for params changes
const params = reactive({
  city: props.filters.city,
  field: props.filters.field,
  direction: props.filters.direction,
});

// Fetch weather data method
async function fetchWeather() {
  errorMessage.value = null;  // Clear previous error message

  try {
    if (!city.value) {  // Use .value to check the reactive value
      errorMessage.value = 'Please enter a city name';
      return;
    }

    const response = await axios.post(route('weather.getData'), {
      city: city.value
    });

    console.log('response: ', response);

    weatherData.value = response.data;
    // Process each forecast's time and store formatted date
    forecastData.value = response.data.forecasts.map(forecast => ({
      ...forecast,
      formattedTime: formatDate(forecast.time)
    }));

    calculationResults.value = {
      temperatureChanges: response.data.temperatureChanges,
      humidityChanges: response.data.humidityChanges,
      pressureChanges: response.data.pressureChanges,
      averageTemperatureChanges: response.data.averageTemperatureChanges,
      averageHumidityChanges: response.data.averageHumidityChanges,
      averagePressureChanges: response.data.averagePressureChanges
    };

  } catch (error) {
    errorMessage.value = 'City not recognized. Please enter a valid city name';
    weatherData.value = null;
    forecastData.value = null;
    calculationResults.value = null;
    localTime.value = getCurrentTime();
    console.error(error);
  }
}

// Set city input method
function setCityInput(input) {
  params.city = input;
  city.value = input; // Update the reactive city variable
}

// Watch for filter changes from props
watch(
  () => props.filters,
  (newFilters) => {
    params.city = newFilters.city;
    params.field = newFilters.field;
    params.direction = newFilters.direction;
  },
  { immediate: true, deep: true }
);

</script>

<template>
    <Head title="Weather" />
    <BreezeAuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800"> Weather API </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.message" class="alert alert-success shadow-lg mb-5">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.flash.message }}</span>
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="relative">
                            <div class="mt-6 mb-6">
                                <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-gray-300">Weather API</label>
                            </div>
                            <div class="overflow-x-auto">
                                <!-- Error message display -->
                                <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>

                                <!-- Weather Search Input -->
                                <div class="weather-search">
                                    <input id="weather-search-input" type="text" v-debounce:300="setCityInput" class="input w-full max-w-xs" placeholder="Enter City..."/>
                                    <button @click="fetchWeather" class="fetch-button mt-4 btn btn-accent block max-w-ws"><strong>Get Weather</strong></button>
                                </div>

                                <!-- Weather Forecast Results -->
                                <div v-if="forecastData" class="mt-10 bg-gray-50 p-5 rounded-lg shadow-md">
                                  <h3 class="text-xl font-semibold mb-4 text-center">Daily Average Forecast for {{ city }}</h3>
                                  <div class="flex space-x-4 overflow-x-auto justify-between">
                                    <div v-for="(forecast, index) in forecastData" :key="index" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative w-64">
                                      <h4 class="text-lg font-semibold mb-2">{{ forecast.formattedTime }}</h4>
                                      <p><strong>Temperature:</strong> {{ forecast.temperature.toFixed(2) }}°C</p>
                                      <p><strong>Feels Like:</strong> {{ forecast.feels_like.toFixed(2) }}°C</p>
                                      <p><strong>Humidity:</strong> {{ forecast.humidity }}%</p>
                                      <p><strong>Pressure:</strong> {{ forecast.pressure }} hPa</p>
                                    </div>
                                  </div>
                                </div>

                                <!-- Weather Calculation Results -->
                                <div v-if="calculationResults" class="mt-10 bg-gray-50 p-5 rounded-lg shadow-md">
                                  <h3 class="text-xl font-semibold mb-4 text-center">Weather Fluctuation Values</h3>
                                  <!-- Temperature, Humidity, Pressure Changes -->
                                  <div v-for="(item, index) in calculationResults" :key="index" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative mb-4">
                                    <h4 class="font-semibold">{{ item }}</h4>
                                    <p>Rate of Change: {{ item }}</p>
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BreezeAuthenticatedLayout>
</template>