# OpenAI JSON Text Transformer

This PHP library enables the transformation of texts using the OpenAI API in JSON mode. The library uses a multi-stage process to generate new, structured content from existing texts.

## How It Works

The library performs the following steps:

1. **Creating an Outline**: Analyzes the input text and creates a structured outline.
2. **Generating a Title**: Creates a suitable title based on the outline.
3. **Generating an Introduction**: Creates an introductory summary.
4. **Generating Sections**: Creates each section and subsection based on the outline.
5. **Compiling the Result**: Combines all generated parts into a structured JSON document.

## Requirements

- PHP 8.2 or higher
- cURL extension for PHP
- OpenAI API key

## Installation

1. Clone or download this repository
2. Copy `config.example.php` to `config.php` and adjust the configuration
3. Make sure your OpenAI API key is entered in the configuration file

## Configuration

The configuration file `config.php` contains the following sections:

### API Settings

```php
'api' => [
    'key' => 'YOUR_OPENAI_API_KEY_HERE', // OpenAI API key
    'model' => 'gpt-4o-mini', // Model for text generation
    'temperature' => 0.7, // Standard temperature (0.0 - 1.0)
    'presence_penalty' => 0.0, // Penalty for new topics (-2.0 - 2.0)
    'frequency_penalty' => 0.0, // Penalty for repetitions (-2.0 - 2.0)
    'max_retries' => 5, // Maximum number of retry attempts for errors
    'retry_delay' => 1, // Wait time in seconds between retry attempts
],
```

### Specific Temperature Settings

```php
'temperatures' => [
    'outline' => 0.7,  // Temperature for creating the outline
    'title' => 0.8,    // Temperature for titles (higher for more creative headlines)
    'introduction' => 0.7, // Temperature for introductions
    'section' => 0.6,   // Temperature for text sections (lower for more factual text)
],
```

You can adjust these temperatures as needed:
- Higher values (0.8-1.0) result in more creative, surprising texts
- Lower values (0.2-0.5) result in more focused, deterministic texts
- Medium values (0.6-0.7) provide a good balance

### System Roles

```php
'roles' => [
    'outline' => 'You are an experienced editor who creates clearly structured outlines.',
    'title' => 'You are an experienced editor who creates concise and appealing titles.',
    'introduction' => 'You are an experienced editor who writes captivating introductions.',
    'section' => 'You are an experienced editor who writes informative and well-structured sections.'
],
```

These system roles are provided to the AI model for each request and influence the style and tone of the generated texts:
- You can adapt the roles to your specific requirements
- For more technical texts, you could use "You are a technical writer who..." for example
- For more creative texts, "You are a creative writer who..." might be more appropriate

### Logging Settings

```php
'logging' => [
    'enabled' => true, // Enable/disable logging
    'path' => __DIR__ . '/logs/api.log', // Path to log file
    'level' => 'DEBUG', // Log level: DEBUG, INFO, WARNING, ERROR
],
```

### Prompts

The prompts determine how OpenAI rewrites the texts. You can adjust them to suit your needs:

- `outline`: Prompt for creating the outline
- `title`: Prompt for generating the title
- `introduction`: Prompt for generating the introduction
- `section`: Prompt for generating the sections

## Usage

The following example shows how to use the library:

```php
// Load the required classes
use OpenAIJsonTransformer\Utils\ConfigManager;
use OpenAIJsonTransformer\Utils\Logger;
use OpenAIJsonTransformer\OpenAI\OpenAIClient;
use OpenAIJsonTransformer\OpenAI\TextTransformer;

// Initialize the components
$configManager = new ConfigManager('config.php');
$logger = new Logger($configManager->getLoggingConfig());
$openaiClient = new OpenAIClient($configManager->getApiConfig(), $logger);
$textTransformer = new TextTransformer($openaiClient, $configManager, $logger);

// Load articles from a JSON file
$articlesJson = file_get_contents('texte.json');
$articles = json_decode($articlesJson, true);

// Transform the articles
$result = $textTransformer->transformArticles($articles);

// Save the result
file_put_contents('output.json', json_encode($result, JSON_PRETTY_PRINT));
```

### Example Script

In the `examples` folder you will find a complete example script:

```bash
php examples/transform_article.php
```

## Input Format

The library expects articles in the following JSON format:

```json
{
  "articles": [
    {
      "title": "Article Title",
      "text": "Article Text..."
    },
    {
      "title": "Another Article Title",
      "text": "Another Article Text..."
    }
  ]
}
```

## Output Format

The library produces JSON in the following format:

```json
{
  "title": "Generated Main Title",
  "introduction": "Generated Introduction...",
  "sections": [
    {
      "heading": "Section Heading",
      "content": "Section Content..."
    },
    {
      "heading": "Section Heading with Subsections",
      "content": "Section Content...",
      "subsections": [
        {
          "heading": "Subsection Heading",
          "content": "Subsection Content..."
        }
      ]
    }
  ]
}
```

## Error Handling

The library implements robust error handling with automatic retry attempts for API errors. All errors are logged in the log file.

## Extensions

The library is designed to be easily extended:

- **Additional LLM Providers**: The structure allows for the integration of other providers such as Anthropic.
- **Additional Transformations**: The transformation system can be extended with additional text processing functions.

## License

This project is licensed under the [MIT License](LICENSE).

## Language

A German version of this documentation is available in [LIESMICH.md](LIESMICH.md). 