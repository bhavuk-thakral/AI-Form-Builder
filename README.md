# AI-Powered Form Builder

A modern, highly responsive AI-powered Form Builder built using **Laravel**, **MySQL**, **Tailwind**, and **ES6 Javascript**. 

---

## 🚀 Live Demo URL & Credentials
- **Live Demo URL**: `https://ai-form-builder.railway.internal/` *(Configure your deployed domain)*
- **Demo Credentials**:
  - Email: `admin@formbuilder.com`
  - Password: `Password123`

---

## 🛠️ Local Installation & Setup Steps

1. **Clone the Repository**:
   ```bash
   git clone <your-repository-url>
   cd ai-form-builder
   ```

2. **Install Composer Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment Variables**:
   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
   *Edit `.env` to configure your MySQL database credentials and your Gemini API key.*

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Link Storage Directory**:
   ```bash
   php artisan storage:link
   ```

7. **Start the Development Server**:
   ```bash
   php artisan serve
   ```

8. **Start the Queue Worker**:
   Run the background process to handle AI generation and edits:
   ```bash
   php artisan queue:work
   ```

---

## 🔑 Environment Variables Checklist (`.env`)
- `APP_KEY`: Generated application security key.
- `DB_CONNECTION`: `mysql` (or `sqlite`/`pgsql`).
- `DB_DATABASE`: Target database name.
- `GEMINI_API_KEY`: API Key for AI co-pilot services.
- `QUEUE_CONNECTION`: `database` (or `redis` in production).

---

## 🏗️ Architecture Overview

### Database Schema (ERD Summary) & Indexes
- **`users`**: Form owners and dashboard administrators.
- **`forms`**: Stores title, status (`draft`/`active`/`generating`), views counter, and the raw `schema` JSON column.
- **`form_versions`**: Historical checkpoints of form schemas to support version rollbacks.
- **`submissions`**: Form completion records, containing submission duration stopwatch latency, IP, and browser user-agent.
- **`submission_answers`**: Value answers mapped to each form field key.
- **`activity_logs`**: Audit trail of form creation, edit, version rollback, and document imports.

### Performance Indexing Strategy
To ensure the builder works efficiently at scale under large submission volumes, the following indexes are declared in the migrations:
1. `forms(user_id)`: Speeds up dashboard retrieval of user forms.
2. `forms(share_token)`: Ensures O(1) lookups for public form rendering.
3. `submissions(form_id)`: Accelerates submission count queries and analytics aggregation.
4. `submission_answers(submission_id, field_key)`: Optimizes answer data retrieval for reports.

---

## 📡 Key API & Web Endpoints

### Form Operations
- `GET /dashboard`: User home view displaying form summaries.
- `POST /forms/generate`: Trigger AI generation from prompt.
- `POST /forms/import`: Upload Word/Excel documents.
- `POST /forms/template`: Create a form pre-populated from a template.
- `GET /forms/{form}/edit`: Visual workspace builder workspace.
- `PATCH /forms/{form}`: Update form schema and metadata.

### Submissions & Reports
- `GET /forms/{form}/submissions`: View submissions list.
- `GET /forms/{form}/analytics`: View metrics and options distributions.
- `GET /forms/{form}/submissions/export`: Export submissions as CSV.
- `GET /share/{token}`: Publicly accessible URL for filling forms.
- `POST /share/{token}/submit`: Save user submission data.

---

## 🤖 AI Prompt Engineering Strategy

### System Prompt & Output Contract
The AI prompt asks the LLM to output a valid JSON schema matching the contract format:
```json
{
  "title": "Form Name",
  "description": "Form Description",
  "fields": [
    {
      "id": "unique_id",
      "type": "text|textarea|dropdown|radio|checkbox|rating...",
      "label": "Field Label",
      "key": "slugified_key",
      "placeholder": "Placeholder...",
      "required": true,
      "options": ["Option A", "Option B"]
    }
  ]
}
```

### Handling Hallucinations
- **Fallback Type Matching**: If the LLM generates an invalid field type, our validator automatically maps it to a standard `text` field.
- **Graceful Repairs**: The job attempts to parse the text. If JSON decoding fails, it triggers regex cleanup blocks to strip markdown syntax and validates fields before persistence.

---

## ⚠️ Known Limitations
1. **IP Views Counting**: Views increment on page load without IP deduplication.
2. **Synchronous File Import**: Imports under 5MB run synchronously for quick canvas redirects. Large uploads could be queued for scale.
3. **No Multi-Language Submissions**: Form translations are stored as distinct forms rather than a dynamic localized translation schema mapping.
