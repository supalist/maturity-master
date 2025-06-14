#!/bin/bash

# File containing the list of files
FILELIST="/var/www/maturitymaster.com/filelist.txt"

# Temporary directory for storing converted files
CONVERTED_DIR="/var/www/maturitymaster.com/converted"

# Email details
RECIPIENT="z@prvt.co"
SUBJECT="Maturity Master Files"
EMAIL_BODY="Please find the converted Maturity Master files attached."

# Step 1: Create the temporary directory if it doesn't exist
mkdir -p "$CONVERTED_DIR"

# Step 2: Read file list into an array and sort it alphabetically
mapfile -t files < <(sort "$FILELIST")

# Step 3: Convert each file in the sorted list to a .txt file
for file in "${files[@]}"; do
    if [[ -f "$file" ]]; then
        base_name=$(basename "$file")
        cp "$file" "$CONVERTED_DIR/$base_name.txt"
        echo "Converted: $file -> $CONVERTED_DIR/$base_name.txt"
    else
        echo "File not found: $file"
    fi
done

# Step 4: Use mutt to send the email with all converted files as attachments
mutt_command="echo \"$EMAIL_BODY\" | mutt -s \"$SUBJECT\""
for txt_file in "$CONVERTED_DIR"/*.txt; do
    if [[ -f "$txt_file" ]]; then
        mutt_command+=" -a \"$txt_file\""
    fi
done
mutt_command+=" -- \"$RECIPIENT\""

# Execute the constructed mutt command
eval $mutt_command

# Step 5: Clean up the converted directory after sending the email
rm -rf "$CONVERTED_DIR"/*

# Final status message
echo "Email sent to $RECIPIENT with converted files, and directory cleaned up."
