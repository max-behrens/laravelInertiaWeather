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

const city = ref('');  // Declare city as a ref
const weatherData = ref(null);
const forecastData = ref(null);
const calculationResults = ref(null);
const errorMessage = ref(null); 
const localTime = ref('');  // Declare reactive state for local time

// Method to get the current system time (formatted)
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

const params = reactive({
  city: props.filters.city,
  field: props.filters.field,
  direction: props.filters.direction,
});

// Add this function near your other utility functions
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

// Add page monitoring
const page = usePage();

// Watch for route changes
watch(
  () => page.url.value,
  (newUrl) => {
    // Force a fresh get request when the URL changes
    Inertia.reload({ preserveScroll: true });
  }
);

// Watch for params changes
watch(params, () => {
  let p = { ...params };
  
  Object.keys(p).forEach(key => {
    if (p[key] === '') {
      delete p[key];
    }
  });

  Inertia.get(
    route('weather.index'), 
    p, 
    { 
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        // Optional: Add any success handling here
      },
      onError: (errors) => {
        console.error('Navigation error:', errors);
      }
    }
  );
}, { deep: true });

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


// Function to set the city input from the user
function setCityInput(input) {
  params.city = input;
  city.value = input; // Update the reactive city variable
}

// Method to fetch weather data from the API
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

    weatherData.value = response.data.current;
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

</script>

<template>

    <Head title="Weather" />
    <BreezeAuthenticatedLayout> <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800"> Weather API </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.message" class="alert alert-success shadow-lg mb-5">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $page.props.flash.message }}</span>
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
            
                        <div class="relative">
                            <div class="mt-6 mb-6">
                                <label for="default-search"
                                    class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-gray-300">Weather API</label>
            
                            </div>
                            <div class="overflow-x-auto">
                            
                                    <!-- Error message display -->
                                    <div v-if="errorMessage" class="error-message">
                                    {{ errorMessage }}
                                    </div>

                                    <div class="weather-search">
                                        <input id="weather-search-input" type="text" v-debounce:300="setCityInput"
                                        class="input w-full max-w-xs" placeholder="Enter City..."/>
                                    <button @click="fetchWeather" class="fetch-button mt-4 btn btn-accent block max-w-ws"><strong>Get Weather</strong></button>
                                    </div>

                                    <!-- Weather Forecast Results -->
                                    <div v-if="forecastData" class="mt-10 bg-gray-50 p-5 rounded-lg shadow-md">
                                      <h3 class="text-xl font-semibold mb-4 text-center">
                                        Daily Average Forecast for {{ city }}
                                      </h3>
                                      <div class="flex space-x-4 overflow-x-auto justify-between">
                                        <div v-for="(forecast, index) in forecastData" 
                                            :key="index"
                                            class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative w-64">
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
                                      
                                      <!-- Temperature Changes -->
                                      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative mb-4">
                                        <h4 class="font-semibold">Temperature Rate of Change (%)</h4>
                                        <p>Rate of Change Between Each Day: {{ calculationResults.temperatureChanges.join(', ') }}</p>
                                        <p>Overall Temperature Volatility: {{ calculationResults.averageTemperatureChanges }}</p>
                                      </div>
                                      
                                      <!-- Humidity Changes -->
                                      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative mb-4">
                                        <h4 class="font-semibold">Humidity Rate of Change (%)</h4>
                                        <p>Rate of Change Between Each Day: {{ calculationResults.humidityChanges.join(', ') }}</p>
                                        <p>Overall Humidity Volatility: {{ calculationResults.averageHumidityChanges }}</p>
                                      </div>
                                      
                                      <!-- Pressure Changes -->
                                      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative mb-4">
                                        <h4 class="font-semibold">Pressure Rate of Change (%)</h4>
                                        <p>Rate of Change Between Each Day: {{ calculationResults.pressureChanges.join(', ') }}</p>
                                        <p>Overall Pressure Volatility: {{ calculationResults.averagePressureChanges }}</p>
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

