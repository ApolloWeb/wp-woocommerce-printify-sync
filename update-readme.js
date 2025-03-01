const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const fetch = require('node-fetch');  // Use node-fetch v2 for CommonJS

// ---------- Configurable Constants ----------
const CACHE_PATH = path.join(process.cwd(), 'descriptionCache.json');
const README_TEMPLATE_PATH = path.join(process.cwd(), 'README_TEMPLATE.txt');
const README_PATH = path.join(process.cwd(), 'README.md');
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;
const OPENAI_MODEL = "gpt-3.5-turbo";  // Using GPT-3.5
const BATCH_SIZE = 1;  // Process files individually

// ---------- Static Excluded Files (added directly in the script) ----------
const EXCLUDED_FILES = [
    'phpunit.xml.dist',
    'phpcs.xml.dist',
    'README.md',
    'wp-woocommerce-printify-sync.php',
    'phpstan.neon',
    '.header_exclude',
    'composer.lock',
    'eslint.config.js',
    'debug.log',
    'error.log',
    '.vscode/settings.json',
    'composer.lock',
    'eslint.config.js'
];

// ---------- Load Cache ----------
function loadCache() {
    if (fs.existsSync(CACHE_PATH)) {
        return JSON.parse(fs.readFileSync(CACHE_PATH, 'utf8'));
    }
    return {}; // Return an empty object if cache doesn't exist
}

function saveCache(cache) {
    fs.writeFileSync(CACHE_PATH, JSON.stringify(cache, null, 2), 'utf8');
}

// ---------- Load README Template ----------
function loadReadmeTemplate() {
    if (!fs.existsSync(README_TEMPLATE_PATH)) {
        console.error("❌ README_TEMPLATE.txt not found!");
        process.exit(1);
    }
    return fs.readFileSync(README_TEMPLATE_PATH, 'utf8');
}

// ---------- Load Exclusions ----------
function loadReadmeExclusions() {
    const excludePath = path.join(process.cwd(), '.readme_exclude');
    if (!fs.existsSync(excludePath)) {
        console.error("❌ .readme_exclude file not found!");
        return new Set();
    }

    return new Set(
        fs.readFileSync(excludePath, 'utf8')
            .split('\n')
            .map(line => line.trim())
            .filter(line => line.length > 0 && !line.startsWith('#'))
            .map(entry => path.resolve(entry)) // Convert to absolute path
    );
}

// ---------- Get All Files ----------
function getAllFiles() {
    let files = execSync(
        `find . -type f ! -path "*/node_modules/*" ! -path "*/vendor/*" ! -path "*/.git/*" ! -path "*/.github/*"`,
        { encoding: 'utf-8' }
    ).split('\n')
    .map(file => file.trim())
    .filter(file => file.length > 0 && fs.existsSync(file));

    const excludedFiles = new Set([...loadReadmeExclusions(), ...EXCLUDED_FILES.map(file => path.resolve(file))]);

    // Filter out files that match any exclusion
    return files.filter(file => {
        const absoluteFile = path.resolve(file);
        const relativeFile = path.relative(process.cwd(), file);
        return !excludedFiles.has(absoluteFile) && !excludedFiles.has(relativeFile);
    });
}

// ---------- Generate File Structure ----------
function generateFileStructure(files) {
    let tree = {};

    files.forEach(file => {
        const parts = file.split(path.sep);
        let current = tree;

        parts.forEach((part, index) => {
            if (index === parts.length - 1) {
                if (!current.files) current.files = [];
                current.files.push(part);
            } else {
                if (!current[part]) current[part] = {};
                current = current[part];
            }
        });
    });

    function renderTree(tree, level = 0) {
        let output = '';
        for (const key in tree) {
            if (key === 'files') continue;
            output += `${'  '.repeat(level)}- **${key}/**\n`;
            if (tree[key].files) {
                tree[key].files.forEach(file => {
                    output += `${'  '.repeat(level + 1)}- ${file}\n`;
                });
            }
            output += renderTree(tree[key], level + 1);
        }
        return output;
    }

    return renderTree(tree);
}

// ---------- AI-Generated File Descriptions (Individual File Processing) ----------
async function generateDescriptionForFile(file, cache) {
    if (!OPENAI_API_KEY) {
        console.error("❌ OpenAI API key missing. Set it as an environment variable: OPENAI_API_KEY");
        return { file, description: "Description could not be generated." };
    }

    const prompt = `Provide a brief description of the file: ${file}. Focus on the main actions (verbs) and key objects (nouns) it handles. Keep it under 20 tokens.`;

    try {
        const response = await fetch("https://api.openai.com/v1/chat/completions", {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${OPENAI_API_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                model: OPENAI_MODEL,
                messages: [{ role: 'system', content: prompt }],
                max_tokens: 20, // Limiting description length to 20 tokens
                temperature: 0.5
            })
        });

        const data = await response.json();

        const description = data.choices[0].message.content.trim() || "Description could not be generated.";
        cache[file] = { description };
        saveCache(cache);
        return { file, description };

    } catch (error) {
        console.error("❌ Error fetching description for file:", error);
        return { file, description: "Description could not be generated." };
    }
}

// ---------- Update README (After All Files Processed) ----------
function updateReadme(newFileStructure, newDescriptions) {
    console.log("📖 Updating README...");

    let readmeContent = loadReadmeTemplate();

    readmeContent = readmeContent.replace(
        `<!-- FILE-STRUCTURE-START --><!-- FILE-STRUCTURE-END -->`,
        `<!-- FILE-STRUCTURE-START -->\n${newFileStructure}\n<!-- FILE-STRUCTURE-END -->`
    );

    readmeContent = readmeContent.replace(
        `<!-- FILE-DESCRIPTIONS-START --><!-- FILE-DESCRIPTIONS-END -->`,
        `<!-- FILE-DESCRIPTIONS-START -->\n${newDescriptions}\n<!-- FILE-DESCRIPTIONS-END -->`
    );

    fs.writeFileSync(README_PATH, readmeContent, 'utf8');
    console.log("✅ README updated with all descriptions!");
}

// ---------- Main Function ----------
async function main() {
    console.log("🔍 Starting README update process...");
    const forceRefresh = process.argv.includes("--refresh");

    // Clear the cache if forceRefresh is set
    if (forceRefresh) {
        console.log("⚡ Cache refresh triggered. Clearing cache.");
        saveCache({});  // Clear the cache by saving an empty object
    }

    let cache = loadCache();
    const allFiles = getAllFiles();

    if (allFiles.length === 0) {
        console.error("❌ No valid files found after exclusions. Check .readme_exclude!");
        return;
    }

    console.log(`📂 Updating file structure... (Force Refresh: ${forceRefresh})`);
    const newFileStructure = generateFileStructure(allFiles);

    const newDescriptions = [];
    const processedFiles = new Set(); // Track processed files to avoid duplicates

    // Process each file individually
    for (let i = 0; i < allFiles.length; i++) {
        const file = allFiles[i];
        console.log(`Processing file ${i + 1} of ${allFiles.length}...`);
        const { file: processedFile, description } = await generateDescriptionForFile(file, cache);

        // Only add new descriptions
        if (!processedFiles.has(processedFile)) {
            processedFiles.add(processedFile);
            newDescriptions.push(`- **${processedFile}**: ${description}`);
        }

        // Debugging: Log the current descriptions
        console.log(`File ${processedFile} description:\n`, description);
    }

    // After all files, update the README
    updateReadme(newFileStructure, newDescriptions.join("\n"));
    console.log("✅ All files processed and README updated.");
}

main();
