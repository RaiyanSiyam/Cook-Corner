<?php
// generate_recipe.php
// Definitive Fix: Restored all missing advanced filter form fields.

include 'header.php';
?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Page Header -->
        <div class="text-center mb-8" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Advanced AI Recipe Generator</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">Precisely control your next meal. Tell our AI chef exactly what you need.</p>
        </div>

        <!-- Input Form -->
        <div class="bg-white rounded-lg shadow-lg p-8" data-aos="fade-up">
            <!-- Main Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ingredients" class="block text-sm font-bold text-gray-700">Ingredients You Have*</label>
                    <textarea id="ingredients" name="ingredients" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., chicken breast, rice, tomatoes, onion, garlic"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Separate ingredients with commas. (Required)</p>
                </div>
                <div>
                    <label for="cuisine" class="block text-sm font-bold text-gray-700">Desired Cuisine</label>
                    <input type="text" id="cuisine" name="cuisine" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="e.g., Italian, Mexican, Indian">
                </div>
            </div>

            <!-- Logical Parameters (RESTORED) -->
             <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
                <div>
                    <label for="diet" class="block text-sm font-bold text-gray-700">Dietary Preference</label>
                    <select id="diet" name="diet" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="None">None</option>
                        <option value="Vegetarian">Vegetarian</option>
                        <option value="Vegan">Vegan</option>
                        <option value="Gluten-Free">Gluten-Free</option>
                    </select>
                </div>
                <div>
                    <label for="meal_type" class="block text-sm font-bold text-gray-700">Meal Type</label>
                    <select id="meal_type" name="meal_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="Any">Any</option>
                        <option value="Breakfast">Breakfast</option>
                        <option value="Lunch">Lunch</option>
                        <option value="Dinner">Dinner</option>
                        <option value="Snack">Snack</option>
                        <option value="Dessert">Dessert</option>
                    </select>
                </div>
                <div>
                    <label for="cook_time" class="block text-sm font-bold text-gray-700">Max Cook Time</label>
                    <select id="cook_time" name="cook_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="Any">Any</option>
                        <option value="15">Under 15 mins</option>
                        <option value="30">Under 30 mins</option>
                        <option value="60">Under 60 mins</option>
                    </select>
                </div>
                 <div>
                    <label for="servings" class="block text-sm font-bold text-gray-700">Serving Size</label>
                    <input type="number" id="servings" name="servings" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value="2" min="1">
                </div>
             </div>
             
            <!-- Nutritional Sliders (RESTORED) -->
             <div class="mt-6 border-t pt-6">
                 <h3 class="text-lg font-bold text-gray-800 mb-4">Nutritional Targets (per serving)</h3>
                 <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label for="calories" class="block text-sm font-bold text-gray-700">Calories: <span id="calories-value">~500</span> kcal</label>
                        <input type="range" id="calories" name="calories" min="200" max="1200" step="50" value="500" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    </div>
                     <div>
                        <label for="protein" class="block text-sm font-bold text-gray-700">Protein: <span id="protein-value">~30</span> g</label>
                        <input type="range" id="protein" name="protein" min="10" max="200" step="5" value="30" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    </div>
                     <div>
                        <label for="fat" class="block text-sm font-bold text-gray-700">Fat: <span id="fat-value">~20</span> g</label>
                        <input type="range" id="fat" name="fat" min="5" max="100" step="5" value="20" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    </div>
                 </div>
             </div>

            <div class="mt-8 text-center">
                <button id="generateBtn" class="bg-blue-600 text-white font-bold py-3 px-8 rounded-full hover:bg-blue-700 transition duration-300 flex items-center justify-center mx-auto">
                    <i data-feather="cpu" class="w-5 h-5 mr-2"></i>
                    <span>Generate Recipes</span>
                </button>
            </div>
        </div>

        <!-- Result Display Area -->
        <div id="resultContainer" class="mt-12 hidden">
            <!-- Loading Spinner -->
            <div id="loadingState" class="text-center py-12 hidden">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Our AI chef is thinking... Please wait.</p>
            </div>
            <!-- Error Message -->
            <div id="errorState" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative">
                <strong class="font-bold">Oops!</strong>
                <span id="errorMessage" class="block sm:inline">Something went wrong.</span>
            </div>
            <!-- Generated Recipe Tabs -->
            <div id="recipeState" class="hidden">
                <div class="mb-4 border-b border-gray-200">
                    <nav id="recipeTabs" class="-mb-px flex space-x-4" aria-label="Tabs"></nav>
                </div>
                <div id="recipeContent"></div>
                <div class="mt-8 text-center">
                    <button id="generateAgainBtn" class="bg-gray-700 text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 transition">
                        <i data-feather="refresh-cw" class="w-4 h-4 mr-2 inline-block"></i>
                        Generate Others
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update slider value displays
    ['calories', 'protein', 'fat'].forEach(id => {
        const slider = document.getElementById(id);
        const display = document.getElementById(`${id}-value`);
        if(slider && display) {
            slider.addEventListener('input', () => {
                display.textContent = `~${slider.value}`;
            });
        }
    });

    const generateBtn = document.getElementById('generateBtn');
    const generateAgainBtn = document.getElementById('generateAgainBtn');
    const resultContainer = document.getElementById('resultContainer');
    const loadingState = document.getElementById('loadingState');
    const errorState = document.getElementById('errorState');
    const errorMessage = document.getElementById('errorMessage');
    const recipeState = document.getElementById('recipeState');
    const recipeTabs = document.getElementById('recipeTabs');
    const recipeContent = document.getElementById('recipeContent');

    const handleGeneration = async () => {
        const payload = {
            ingredients: document.getElementById('ingredients').value.trim(),
            cuisine: document.getElementById('cuisine').value.trim(),
            diet: document.getElementById('diet').value,
            meal_type: document.getElementById('meal_type').value,
            cook_time: document.getElementById('cook_time').value,
            servings: document.getElementById('servings').value,
            calories: document.getElementById('calories').value,
            protein: document.getElementById('protein').value,
            fat: document.getElementById('fat').value,
        };

        if (!payload.ingredients) {
            alert('Please enter at least one ingredient.');
            return;
        }

        resultContainer.classList.remove('hidden');
        loadingState.classList.remove('hidden');
        errorState.classList.add('hidden');
        recipeState.classList.add('hidden');

        const originalBtnText = generateBtn.innerHTML;
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span>Generating...</span>';
        
        try {
            const response = await fetch('api_generate_recipe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'The server responded with an error.');
            }

            displayRecipes(result.recipes);

        } catch (error) {
            console.error('Generation failed:', error);
            errorMessage.textContent = error.message;
            loadingState.classList.add('hidden');
            errorState.classList.remove('hidden');
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = originalBtnText;
            feather.replace();
        }
    };

    generateBtn.addEventListener('click', handleGeneration);
    generateAgainBtn.addEventListener('click', handleGeneration);

    function displayRecipes(recipes) {
        recipeTabs.innerHTML = '';
        recipeContent.innerHTML = '';

        (recipes || []).forEach((recipe, index) => {
            // Create Tab Button
            const tabButton = document.createElement('button');
            tabButton.className = `whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${index === 0 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`;
            tabButton.textContent = `Recipe ${index + 1}`;
            tabButton.dataset.tab = `recipe-${index}`;
            recipeTabs.appendChild(tabButton);

            // Create Content Panel
            const contentPanel = document.createElement('div');
            contentPanel.id = `recipe-${index}`;
            contentPanel.className = `bg-white rounded-lg shadow-lg overflow-hidden p-8 ${index > 0 ? 'hidden' : ''}`;
            
            let ingredientsHtml = (recipe.ingredients || []).map(item => `
                <li class="flex items-start">
                    <i data-feather="check-circle" class="w-5 h-5 text-blue-500 mr-3 mt-1 flex-shrink-0"></i>
                    <span>${item}</span>
                </li>`).join('');
            
            let instructionsHtml = (recipe.instructions || []).map((step, i) => `
                <li class="flex items-start">
                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold mr-4">${i + 1}</div>
                    <p class="text-gray-700 leading-relaxed pt-1">${step}</p>
                </li>`).join('');

            contentPanel.innerHTML = `
                <h2 class="text-3xl font-bold text-gray-900">${recipe.title || 'Untitled Recipe'}</h2>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mt-8">
                    <div class="lg:col-span-1">
                        <h3 class="text-xl font-bold text-gray-800 border-b-2 border-blue-500 pb-2 mb-4">Ingredients</h3>
                        <ul class="space-y-2">${ingredientsHtml}</ul>
                    </div>
                    <div class="lg:col-span-2">
                        <h3 class="text-xl font-bold text-gray-800 border-b-2 border-blue-500 pb-2 mb-4">Instructions</h3>
                        <ol class="space-y-4">${instructionsHtml}</ol>
                    </div>
                </div>`;
            recipeContent.appendChild(contentPanel);
        });
        
        // Add tab switching logic
        document.querySelectorAll('#recipeTabs button').forEach(button => {
            button.addEventListener('click', function() {
                // Deactivate all
                document.querySelectorAll('#recipeTabs button').forEach(btn => {
                    btn.className = 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                });
                document.querySelectorAll('#recipeContent > div').forEach(panel => {
                    panel.classList.add('hidden');
                });
                // Activate clicked
                this.className = 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600';
                document.getElementById(this.dataset.tab).classList.remove('hidden');
            });
        });

        feather.replace();
        loadingState.classList.add('hidden');
        recipeState.classList.remove('hidden');
    }
});
</script>

<?php
include 'footer.php';
?>

