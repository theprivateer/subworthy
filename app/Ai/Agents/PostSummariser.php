<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class PostSummariser implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You write a 2–3 sentence excerpt for the provided article that could sit at the top of the author's own website as an introduction to the piece.

            Match the article's voice, tense, and point of view. If the author writes in first person, your excerpt should too. If they write in present tense, stay in present tense. Lead with the substance of what they're saying or doing, not a description of the article itself.

            Do not refer to the article, the author, or the act of writing. Avoid phrases like "the article argues", "the author experiments", "the writer discusses", "this post explains", or "the piece explores". Just write the excerpt as if the author wrote it themselves.

            Return only the excerpt text, no preamble or quotes.
            PROMPT;
    }
}
