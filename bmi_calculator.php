<?php

include 'header.php';
?>

<div class="bg-gray-50 min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">

        <!-- Calculator Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 space-y-8">
            <div class="text-center">
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-800">BMI Calculator</h1>
                <p class="text-gray-500 mt-2">Calculate your Body Mass Index quickly and easily.</p>
            </div>

            <!-- Unit Selector -->
            <div class="flex justify-center bg-gray-100 p-1 rounded-full">
                <button id="metric-btn" class="w-full py-2 px-4 rounded-full font-semibold text-sm bg-blue-500 text-white shadow">Metric (kg, cm)</button>
                <button id="imperial-btn" class="w-full py-2 px-4 rounded-full font-semibold text-sm text-gray-600">Imperial (lbs, ft, in)</button>
            </div>

            <!-- Form -->
            <form id="bmi-form" class="space-y-6">

                <!-- Metric Inputs (Visible by default) -->
                <div id="metric-inputs">
                    <div class="space-y-4">
                        <div>
                            <label for="cm" class="block text-sm font-medium text-gray-700 mb-1">Height (cm)</label>
                            <input type="number" id="cm" placeholder="e.g., 175" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="kg" class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                            <input type="number" id="kg" placeholder="e.g., 70" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Imperial Inputs (Hidden by default) -->
                <div id="imperial-inputs" class="hidden">
                     <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="ft" class="block text-sm font-medium text-gray-700 mb-1">Height (ft)</label>
                            <input type="number" id="ft" placeholder="e.g., 5" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                         <div>
                            <label for="in" class="block text-sm font-medium text-gray-700 mb-1">(in)</label>
                            <input type="number" id="in" placeholder="e.g., 9" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="lbs" class="block text-sm font-medium text-gray-700 mb-1">Weight (lbs)</label>
                        <input type="number" id="lbs" placeholder="e.g., 155" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-white bg-blue-600 hover:bg-blue-700 font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        Calculate BMI
                    </button>
                </div>
            </form>

            <!-- Result Display Area -->
            <div id="result-container" class="hidden text-center pt-6 border-t">
                <p class="text-gray-600">Your BMI is</p>
                <p id="bmi-value" class="text-5xl font-bold my-2"></p>
                <p id="bmi-category" class="font-semibold px-4 py-2 rounded-full inline-block"></p>
            </div>

        </div>

        <!-- BMI Information Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mt-8">
             <h2 class="text-2xl font-bold text-gray-800 text-center mb-4">Understanding BMI</h2>
             <p class="text-gray-600 mb-6 text-center">Body Mass Index (BMI) is a measure that uses your height and weight to work out if your weight is healthy. The BMI calculation divides an adult's weight by the square of their height.</p>
             <ul class="space-y-3">
                 <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-blue-400 mt-1 mr-3 flex-shrink-0"></span><div><strong class="text-gray-800">Below 18.5:</strong> Underweight</div></li>
                 <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-green-400 mt-1 mr-3 flex-shrink-0"></span><div><strong class="text-gray-800">18.5 – 24.9:</strong> Healthy Weight</div></li>
                 <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-yellow-400 mt-1 mr-3 flex-shrink-0"></span><div><strong class="text-gray-800">25.0 – 29.9:</strong> Overweight</div></li>
                 <li class="flex items-start"><span class="w-4 h-4 rounded-full bg-red-400 mt-1 mr-3 flex-shrink-0"></span><div><strong class="text-gray-800">30.0 and Above:</strong> Obesity</div></li>
             </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Element References ---
    const metricBtn = document.getElementById('metric-btn');
    const imperialBtn = document.getElementById('imperial-btn');
    const metricInputs = document.getElementById('metric-inputs');
    const imperialInputs = document.getElementById('imperial-inputs');
    const form = document.getElementById('bmi-form');
    const resultContainer = document.getElementById('result-container');
    const bmiValueEl = document.getElementById('bmi-value');
    const bmiCategoryEl = document.getElementById('bmi-category');

    let currentUnit = 'metric';

    // --- Event Listeners ---

    // Switch to Metric units
    metricBtn.addEventListener('click', () => {
        currentUnit = 'metric';
        metricInputs.classList.remove('hidden');
        imperialInputs.classList.add('hidden');
        metricBtn.classList.add('bg-blue-500', 'text-white', 'shadow');
        imperialBtn.classList.remove('bg-blue-500', 'text-white', 'shadow');
    });

    // Switch to Imperial units
    imperialBtn.addEventListener('click', () => {
        currentUnit = 'imperial';
        imperialInputs.classList.remove('hidden');
        metricInputs.classList.add('hidden');
        imperialBtn.classList.add('bg-blue-500', 'text-white', 'shadow');
        metricBtn.classList.remove('bg-blue-500', 'text-white', 'shadow');
    });

    // Handle form submission
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        calculateBMI();
    });

    // --- Main Calculation Logic ---
    function calculateBMI() {
        let bmi = 0;

        if (currentUnit === 'metric') {
            const cm = parseFloat(document.getElementById('cm').value);
            const kg = parseFloat(document.getElementById('kg').value);
            if (cm > 0 && kg > 0) {
                const heightInMeters = cm / 100;
                bmi = kg / (heightInMeters * heightInMeters);
            }
        } else { // Imperial
            const ft = parseFloat(document.getElementById('ft').value) || 0;
            const inches = parseFloat(document.getElementById('in').value) || 0;
            const lbs = parseFloat(document.getElementById('lbs').value);
            if ((ft > 0 || inches > 0) && lbs > 0) {
                const totalInches = (ft * 12) + inches;
                bmi = (lbs / (totalInches * totalInches)) * 703;
            }
        }

        if (bmi > 0) {
            displayResult(bmi);
        } else {
             resultContainer.classList.add('hidden'); // Hide if input is invalid
        }
    }
    
    // --- Display and Style the Result ---
    function displayResult(bmi) {
        const roundedBmi = bmi.toFixed(1);
        bmiValueEl.textContent = roundedBmi;

        let category = '';
        let colorClasses = '';

        if (bmi < 18.5) {
            category = 'Underweight';
            colorClasses = 'bg-blue-100 text-blue-800';
        } else if (bmi >= 18.5 && bmi <= 24.9) {
            category = 'Healthy Weight';
            colorClasses = 'bg-green-100 text-green-800';
        } else if (bmi >= 25 && bmi <= 29.9) {
            category = 'Overweight';
            colorClasses = 'bg-yellow-100 text-yellow-800';
        } else {
            category = 'Obesity';
            colorClasses = 'bg-red-100 text-red-800';
        }

        bmiCategoryEl.textContent = category;
        // Reset classes before adding new ones
        bmiCategoryEl.className = 'font-semibold px-4 py-2 rounded-full inline-block'; 
        bmiCategoryEl.classList.add(...colorClasses.split(' '));
        
        resultContainer.classList.remove('hidden');
    }
});
</script>

<?php
include 'footer.php';
?>
