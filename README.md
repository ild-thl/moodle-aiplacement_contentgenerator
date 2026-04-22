# AI Placement: Content Generator

`aiplacement_contentgenerator` is a Moodle plugin (plugin type `aiplacement`) that automatically creates learning videos from existing course content.

Typical workflow:
- Select source content (for example PDF, label, page, file content)
- AI-based preprocessing and structuring
- Generate Marp slides
- Render slide images
- Generate speaker notes and audio tracks
- Merge everything into a final video
- Store the video in Moodle file storage

## Requirements

### Moodle
- Moodle `4.5.x`
- PHP and system requirements according to Moodle 4.5

### Required additional plugin
- AI provider plugin: `aiprovider_myai`  
  Repository: [ild-thl/moodle-aiprovider_myai](https://github.com/ild-thl/moodle-aiprovider_myai)

### Server dependencies

The following tools must be installed on the Moodle server and executable by the web server user:

- `ffmpeg` (video generation and merging)
- `marp` / `@marp-team/marp-cli` (slide rendering)
- `google-chrome` (headless browser required by Marp/Puppeteer)
- `pdftoppm` from `poppler-utils` (server-side PDF page rendering)

Recommended checks:

```bash
node --version
/usr/local/bin/marp --version
/usr/bin/ffmpeg -version
which google-chrome
which pdftoppm
```

Note: In Linux deployments, the web server user (for example `www-data`) must be allowed to execute these binaries.

## Installation

1. Copy the plugin into the Moodle directory:
   - Target path: `ai/placement/contentgenerator`
2. Run Moodle upgrade:
   - via Web UI (`/admin`) or CLI (`admin/cli/upgrade.php`)
3. Ensure the AI provider plugin is installed and configured:
   - `aiprovider_myai`
4. Install server dependencies:
   - Node.js (for Marp)
   - Marp CLI
   - Google Chrome
   - ffmpeg
   - poppler-utils (`pdftoppm`)
5. Verify file and execution permissions:
   - Moodle must be able to write to temporary directories and start required processes.

## Configuration

Configure the plugin in the plugin settings:

- `pathtomarp`  
  Full path to the Marp executable (for example `/usr/local/bin/marp`)
- `pathtoffmpeg`  
  Full path to the ffmpeg executable (for example `/usr/bin/ffmpeg`)
- `pathtopdftoppm`  
  Full path to the pdftoppm executable (for example `/usr/bin/pdftoppm`)
- Additional plugin options according to `settings.php`

Important:
- Always use absolute paths.
- In Linux environments, Marp/Chrome must work in a headless task-runner context.
- PDF page rendering is fully server-side via `pdftoppm` (no client-side PDF-to-base64 conversion).

## Usage notes

- The plugin runs asynchronously using an ad-hoc task.
- Processing may take several minutes, depending on data volume and model latency.
- Intermediate artifacts are created in temporary directories and cleaned up after processing.
- The final video is stored in the user file area (depending on implementation/workflow state).
- If errors occur, check task logs (`mtrace`) and configured system paths first.
- The form submits only selected file IDs; large PDF payloads are not posted from the browser.

## Course usage (step by step)

### 1) Open the plugin in the course
1. Open the course.
2. Click **More** in the top navigation.
3. Select **Generate AI content**.

### 2) Select content for generation
1. In the form, select the desired course content (for example files/PDFs, pages, labels).
2. Optionally enter additional instructions (this text is used in the refinement prompt as `{{additionalinstructions}}`).
3. Click **Generate AI content**.

### 3) What happens immediately after clicking
1. The plugin creates an asynchronous Moodle ad-hoc task.
2. The page shows a confirmation that generation has started.
3. The user can continue working in the course while processing runs in the background.

### 4) What happens in the background
1. Selected content is collected and merged.
2. PDF files are converted to images server-side (`pdftoppm`) and included in the text pipeline.
3. Content is refined with AI (refinement step).
4. Marp slides are generated from the refined content.
5. Marp renders slides into images.
6. AI generates speaker text for each slide.
7. TTS generates one audio file per slide.
8. `ffmpeg` builds slide video segments and merges them into a final video.
9. Temporary files are cleaned up afterwards.

### 5) E-mail notification
1. After completion, Moodle sends an e-mail to the user who started the process.
2. On success, the e-mail confirms that generation has finished.
3. On failure, the e-mail contains a structured error summary.

### 6) Where to find the generated video
1. The final video is stored in the user file area.
2. In the current workflow, it is placed in **Private files** of the initiating user.
3. The completion e-mail references the course context; the file itself is available in the user file area.

## Technical flow (simplified)

1. Extract/aggregate source content
2. Render selected PDF files to page images server-side (`pdftoppm`)
3. Refine text using an LLM
4. Generate Marp markdown
5. Render slides to images (Marp + Chrome)
6. Generate speaker text
7. Create text-to-speech output per slide
8. Generate video segments per slide (`ffmpeg`)
9. Merge segment videos (`ffmpeg concat`)
10. Store the final result in Moodle

## Architecture and connected systems

The plugin contains the orchestration workflow inside Moodle and delegates model calls to the Moodle AI subsystem and configured provider.

External system components:
- Moodle AI subsystem + provider (`aiprovider_myai`)
- API endpoints of the used AI models (external model hosting)
- Local rendering/media tools (`pdftoppm`, `marp`, `google-chrome`, `ffmpeg`)

This keeps the architecture decoupled:
- Moodle plugin = process logic, data flow, user integration
- AI provider = authentication, routing, model access
- External models = actual inference (text, vision, TTS)

## Model capabilities per processing step

For stable operation, at least the following model capabilities should be available:

- **Image-to-text / vision model**  
  For PDF page analysis (OCR/semantic extraction)
- **Text generation model (LLM)**  
  For content refinement, slide structure (Marp), and speaker text
- **Text-to-speech model (TTS)**  
  For slide-level audio tracks

## Operations and troubleshooting

Typical checks when issues occur:
- Are `pathtomarp` / `pathtoffmpeg` / `pathtopdftoppm` set correctly?
- Is `google-chrome` available to the server user?
- Is `pdftoppm` installed (poppler-utils) and executable by the server user?
- Does the ad-hoc task run without timeout?
- Are temporary directories writable/deletable?
- Is the AI provider reachable and configured correctly?
