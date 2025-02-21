<script setup>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';
import axios from 'axios';
import ChartComponent from '@/Components/ChartComponent.vue';

const userInput = ref('');
const aiResponse = ref('');

const askAI = async () => {
    if (!userInput.value.trim()) return;

    try {
        const { data } = await axios.post('/api/ask-openai', { user_input: userInput.value });
        aiResponse.value = data.aiResponse;
    } catch (error) {
        console.error('Error fetching AI response:', error);
        aiResponse.value = 'Error: Unable to get a response from AI.';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <BreezeAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- AI Interaction Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold">Ask OpenAI</h3>

                    <input v-model="userInput" type="text" placeholder="Enter a question..." 
                        class="border p-2 w-full mt-3" />

                    <button @click="askAI" class="bg-blue-500 text-white px-4 py-2 mt-2 rounded">
                        Ask AI
                    </button>

                    <div v-if="aiResponse" class="mt-4 p-4 bg-gray-100 rounded">
                        <strong>AI Response:</strong>
                        <p class="mt-1 text-gray-700">{{ aiResponse }}</p>
                    </div>
                </div>

                <!-- Chart Component -->
                <ChartComponent />
            </div>
        </div>
    </BreezeAuthenticatedLayout>
</template>
