
import pandas as pd
import zipfile
import os
import re
import json

# --- CONFIGURATION: CHANGE THESE VALUES ---
# The path to the ZIP file you downloaded from Kaggle.
ZIP_FILE_PATH = 'archive.zip' 
# The name of the folder where the files will be extracted.
EXTRACT_TO_FOLDER = 'recipe_dataset'
# The name of the main CSV file inside the zip. You MUST check this after unzipping.
# UPDATED: Switched to the "RAW" file which contains the correct columns and readable text.
CSV_FILE_NAME = 'RAW_recipes.csv' 
# The name of the final, clean text file we will create.
OUTPUT_FILE_PATH = 'formatted_recipes.txt'
# How many rows to process? For testing, use a smaller number like 10000.
# For the full run, set this to None.
ROWS_TO_PROCESS = 10000 
# --- END OF CONFIGURATION ---

def main():
    """Main function to run the data processing pipeline."""
    
    # --- 1. EXTRACT ---
    if not os.path.exists(EXTRACT_TO_FOLDER):
        print(f"Extracting '{ZIP_FILE_PATH}'...")
        # Check if the zip file exists before trying to open it
        if not os.path.exists(ZIP_FILE_PATH):
            print(f"ERROR: The ZIP file '{ZIP_FILE_PATH}' was not found.")
            print("Please make sure the ZIP file is in the same directory as this script and the name is correct.")
            return
        with zipfile.ZipFile(ZIP_FILE_PATH, 'r') as zip_ref:
            zip_ref.extractall(EXTRACT_TO_FOLDER)
        print("Extraction complete.")
    else:
        print(f"'{EXTRACT_TO_FOLDER}' already exists. Skipping extraction.")

    csv_path = os.path.join(EXTRACT_TO_FOLDER, CSV_FILE_NAME)
    if not os.path.exists(csv_path):
        print(f"ERROR: The CSV file '{CSV_FILE_NAME}' was not found in the extracted folder.")
        print("Please check the correct filename inside the ZIP and update the 'CSV_FILE_NAME' variable.")
        return

    # --- 2. EXPLORE ---
    print(f"Loading data from '{csv_path}'. This might take a while...")
    # These column names should be correct for RAW_recipes.csv
    required_columns = ['name', 'ingredients', 'steps'] 
    
    try:
        # We need to specify the full path to the extracted file now
        full_csv_path = os.path.join(EXTRACT_TO_FOLDER, CSV_FILE_NAME)
        df = pd.read_csv(full_csv_path, usecols=required_columns, nrows=ROWS_TO_PROCESS)
        print("Data loaded successfully. Here's a sample:")
        print(df.head())
        print("\nData Info:")
        df.info()
    except ValueError as e:
        print(f"ERROR loading CSV: {e}")
        print("Please check that the 'required_columns' list matches the actual column names in your CSV file.")
        print("Common alternatives are: ['Title', 'Ingredients', 'Instructions'] or ['recipe_name', 'ingredient_list', 'directions']")
        return
        
    # --- 3. CLEAN & FILTER ---
    print("\nCleaning and filtering data...")
    original_rows = len(df)
    
    # Drop any rows where the essential parts of a recipe are missing.
    df.dropna(subset=required_columns, inplace=True)
    
    # More advanced cleaning can be done here. For example, removing recipes
    # with very short instructions or few ingredients.
    df = df[df['steps'].str.len() > 20]
    df = df[df['ingredients'].str.count(',') > 1] # At least two ingredients

    cleaned_rows = len(df)
    print(f"Removed {original_rows - cleaned_rows} rows with missing or invalid data.")
    print(f"Remaining rows: {cleaned_rows}")

    # --- 4. TRANSFORM & FORMAT ---
    print("\nFormatting recipes into a single text string each...")
    
    def format_recipe(row):
        """
        Takes a row of the DataFrame and converts it into our desired
        text format: [TITLE]...[INGREDIENTS]...[INSTRUCTIONS]...
        """
        title = str(row['name']).strip()
        
        try:
            ingredients_list = eval(row['ingredients'])
            ingredients = "; ".join([item.strip() for item in ingredients_list])
        except (SyntaxError, NameError):
            ingredients = str(row['ingredients']).replace('\n', ' ').strip()

        try:
            instructions_list = eval(row['steps'])
            instructions = "; ".join([item.strip() for item in instructions_list])
        except (SyntaxError, NameError):
            instructions = str(row['steps']).replace('\n', ' ').strip()
        
        # The final format is crucial for the model to learn the pattern.
        return f"[TITLE] {title} [INGREDIENTS] {ingredients} [INSTRUCTIONS] {instructions}"

    df['formatted_recipe'] = df.apply(format_recipe, axis=1)

    print("Formatting complete. Here's a sample of the formatted text:")
    print(df['formatted_recipe'].head())

    # --- 5. LOAD (SAVE) ---
    print(f"\nSaving formatted recipes to '{OUTPUT_FILE_PATH}'...")
    with open(OUTPUT_FILE_PATH, 'w', encoding='utf-8') as f:
        for recipe in df['formatted_recipe']:
            f.write(recipe + '\n')

    print("Process complete!")
    print(f"Your clean, formatted data is ready in '{OUTPUT_FILE_PATH}'.")


if __name__ == '__main__':
    main()

