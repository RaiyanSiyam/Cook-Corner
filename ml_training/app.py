# ml_training/app.py
# This script creates a simple web server (API) to serve your recipe model.

from flask import Flask, request, jsonify
from transformers import GPT2LMHeadModel, GPT2Tokenizer
import torch
import os

# --- NEW: BULLETPROOF PATHING ---
# Get the absolute path of the directory where this script is located.
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
# Join the script's directory path with the model folder's name.
# This creates a reliable, absolute path to the model, regardless of where the script is run from.
MODEL_PATH = os.path.join(SCRIPT_DIR, 'recipe_generator_model')
# --- END NEW PATHING ---


# Initialize the Flask application
app = Flask(__name__)

# --- DEBUGGING BLOCK ---
# This block will print exactly what the script sees before trying to load the model.
print("\n" + "="*50)
print("--- SCRIPT-LEVEL DEBUGGING ---")
print(f"Script's Directory: {SCRIPT_DIR}")
print(f"Absolute Path to Model: {MODEL_PATH}")

if os.path.exists(MODEL_PATH) and os.path.isdir(MODEL_PATH):
    print("\n[SUCCESS] The model directory exists.")
    print("Files found inside the directory:")
    try:
        files = os.listdir(MODEL_PATH)
        if not files:
            print("- The directory is empty.")
        else:
            for f in files:
                print(f"- {f}")
    except Exception as e:
        print(f"[ERROR] Could not list files in directory: {e}")
else:
    print("\n[FAILURE] The model directory does NOT exist at the path above.")
    print("Please ensure the 'recipe_generator_model' folder is in the same directory as this script.")

print("="*50 + "\n")
# --- END DEBUGGING BLOCK ---


# --- LOAD MODEL AND TOKENIZER ---
# We load the model and tokenizer only once when the server starts.
print("Loading model and tokenizer...")
try:
    # Use the GPU if available (important for speed)
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    # Load the model and tokenizer using the absolute path
    model = GPT2LMHeadModel.from_pretrained(MODEL_PATH).to(device)
    tokenizer = GPT2Tokenizer.from_pretrained(MODEL_PATH)
    
    # Set the pad token if it's not already set
    if tokenizer.pad_token is None:
        tokenizer.pad_token = tokenizer.eos_token

    print(f"Model loaded successfully on device: {device}")
    model_loaded = True

except Exception as e:
    print(f"CRITICAL ERROR LOADING MODEL: {e}")
    model_loaded = False
    model = None
    tokenizer = None
# --- END MODEL LOADING ---


@app.route('/generate_recipe', methods=['POST'])
def generate_recipe():
    """
    This is the main API endpoint. It expects a JSON request with a 'prompt'.
    """
    if not model_loaded:
        return jsonify({"error": "Model is not loaded. Please check the server logs for the critical error."}), 500

    data = request.get_json()
    if not data or 'prompt' not in data:
        return jsonify({"error": "No prompt provided in the request."}), 400
        
    prompt = data['prompt']
    
    try:
        input_ids = tokenizer.encode(prompt, return_tensors='pt').to(device)
        output = model.generate(
            input_ids, max_length=250, num_beams=5, no_repeat_ngram_size=2,
            early_stopping=True, temperature=0.95, top_k=50, top_p=0.95
        )
        generated_text = tokenizer.decode(output[0], skip_special_tokens=True)
        return jsonify({"generated_recipe": generated_text})

    except Exception as e:
        print(f"Error during generation: {e}")
        return jsonify({"error": "An error occurred while generating the recipe."}), 500


if __name__ == '__main__':
    # We must disable the reloader for this pathing to work reliably in debug mode
    app.run(host='0.0.0.0', port=5000, debug=True, use_reloader=False)

