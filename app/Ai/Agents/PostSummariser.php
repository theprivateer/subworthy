<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class PostSummariser implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You analyse an article and return two things: a short excerpt and a list of broad topic categories.

            For the summary: write a 2–3 sentence excerpt that could sit at the top of the author's own website as an introduction to the piece. Match the article's voice, tense, and point of view. If the author writes in first person, your excerpt should too. If they write in present tense, stay in present tense. Lead with the substance of what they're saying or doing, not a description of the article itself. Do not refer to the article, the author, or the act of writing. Avoid phrases like "the article argues", "the author experiments", "the writer discusses", "this post explains", or "the piece explores". Just write the excerpt as if the author wrote it themselves.

            For the themes: return 1–3 broad, lowercase categories that describe the article's subject area. Use reusable buckets that will match across many posts — for example "technology", "politics", "culture", "business", "science", "health", "environment", "sport", "finance", "education". Prefer fewer, broader categories over many specific ones. No hashtags or punctuation.
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'themes'  => $schema->array()
                ->items($schema->string())
                ->required(),
        ];
    }
}
