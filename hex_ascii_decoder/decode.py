import re

def decode_mixed_string(mixed_str):
    try:
        # Replace the problematic sequences with appropriate values
        mixed_str = mixed_str.replace('\\xd', '\\x0d').replace('\\xa', '\\x0a')

        # First, decode the unicode-escape sequences
        decoded_str = bytes(mixed_str, 'utf-8').decode('unicode-escape')

        # Next, decode the resulting string as Latin-1
        decoded_str = decoded_str.encode('latin-1').decode('utf-8')

        return decoded_str
    except (UnicodeDecodeError, ValueError):
        # Return the original string if decoding fails
        return mixed_str

def process_file(input_file_path, output_file_path):
    with open(input_file_path, 'r', encoding='utf-8') as infile, open(output_file_path, 'w', encoding='utf-8') as outfile:
        for line in infile:
            line = line.strip()
            # Check if the line contains escape sequences
            if re.search(r'\\x[0-9a-fA-F]{2}|\\u[0-9a-fA-F]{4}|\\[0-7]{1,3}', line):
                decoded_line = decode_mixed_string(line)
            else:
                decoded_line = line
            outfile.write(decoded_line + '\n')

# Example usage
if __name__ == "__main__":
    input_file_path = './input.txt'  # Path to the input file
    output_file_path = './output.txt'  # Path to the output file

    process_file(input_file_path, output_file_path)
    print(f"Processed lines written to {output_file_path}")


