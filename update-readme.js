const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

// Function to read the `.readme_exclude` file and return the excluded files
function getExcludedFiles() {
  const excludeFilePath = path.join(process.cwd(), ".readme_exclude");

  if (!fs.existsSync(excludeFilePath)) {
    return [];  // Return an empty array if `.readme_exclude` does not exist
  }

  const excludeContent = fs.readFileSync(excludeFilePath, "utf8");
  const excludedFiles = excludeContent
    .split("\n")
    .map(line => line.trim())
    .filter(line => line.length > 0 && !line.startsWith("#"));

  return excludedFiles;
}

// Function to get the list of changed files (from the last commit)
function getChangedFiles() {
  try {
    // Get the list of files changed in the last commit compared to the previous commit
    const output = execSync("git diff --name-only HEAD^ HEAD", { encoding: "utf-8" });
    const files = output.split("\n").filter(file => file.trim() !== "");
    return files;
  } catch (error) {
    console.error("❌ Error getting changed files:", error);
    return [];
  }
}

// Function to update the README file
function updateReadme(changedFiles, excludedFiles) {
  const readmePath = path.join(process.cwd(), "README.md");

  if (!fs.existsSync(readmePath)) {
    console.error("❌ README.md not found.");
    return;
  }

  let readmeContent = fs.readFileSync(readmePath, "utf8");

  // Filter out excluded files from the changed files
  const filesToInclude = changedFiles.filter(file => !excludedFiles.includes(file));

  if (filesToInclude.length === 0) {
    console.log("⚠️ No files to update for README after applying exclusions.");
    return;
  }

  // Create a section for changed files in the README
  let changedFilesSection = "## Changed Files:\n";
  filesToInclude.forEach(file => {
    // Add a description based on the file type or content
    let description = `(No description available for ${file})`;

    if (file.endsWith(".js")) {
      description = "Update JavaScript file.";
    } else if (file.endsWith(".php")) {
      description = "Update PHP file.";
    } else if (file.endsWith(".css")) {
      description = "Update CSS styles.";
    }

    // Add file and description to the section
    changedFilesSection += `- **${file}**: ${description}\n`;
  });

  // Append the changed files section to the README (or update if it exists)
  const sectionStart = "## Changed Files:";
  const sectionEnd = "## Other Sections";  // Define the start of the next section if any

  const sectionRegEx = new RegExp(`(${sectionStart}[\\s\\S]*?${sectionEnd})`, "g");
  const updatedReadmeContent = readmeContent.replace(sectionRegEx, `${sectionStart}\n${changedFilesSection}\n${sectionEnd}`);

  // Write the updated content back to the README
  fs.writeFileSync(readmePath, updatedReadmeContent, "utf8");
  console.log("✅ README.md updated successfully!");
}

// Main function to execute
function main() {
  // Get the changed files
  const changedFiles = getChangedFiles();
  // Get the excluded files from .readme_exclude
  const excludedFiles = getExcludedFiles();

  if (changedFiles.length > 0) {
    updateReadme(changedFiles, excludedFiles);
  } else {
    console.log("⚠️ No files to update for README.");
  }
}

main();
