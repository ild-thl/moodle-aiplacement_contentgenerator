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

## Usage notes

- The plugin runs asynchronously using an ad-hoc task.
- Processing may take several minutes, depending on data volume and model latency.
- Intermediate artifacts are created in temporary directories and cleaned up after processing.
- The final video is stored in the user file area (depending on implementation/workflow state).
- If errors occur, check task logs (`mtrace`) and configured system paths first.

## Technical flow (simplified)

1. Extract/aggregate source content
2. Refine text using an LLM
3. Generate Marp markdown
4. Render slides to images (Marp + Chrome)
5. Generate speaker text
6. Create text-to-speech output per slide
7. Generate video segments per slide (`ffmpeg`)
8. Merge segment videos (`ffmpeg concat`)
9. Store the final result in Moodle

## Architecture and connected systems

The plugin contains the orchestration workflow inside Moodle and delegates model calls to the Moodle AI subsystem and configured provider.

External system components:
- Moodle AI subsystem + provider (`aiprovider_myai`)
- API endpoints of the used AI models (external model hosting)
- Local rendering/media tools (`marp`, `google-chrome`, `ffmpeg`)

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
