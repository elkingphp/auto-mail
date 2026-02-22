<?php

namespace App\Services\Delivery;

use App\Models\EmailTemplate;

class TemplateRenderer
{
    /**
     * Render a template string with variables.
     *
     * @param string $content
     * @param array $variables Key-value pairs matching placeholders like {{key}}
     * @return string
     */
    public function renderString(string $content, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, (string) $value, $content);
        }
        return $content;
    }

    /**
     * Render an EmailTemplate entity.
     *
     * @param EmailTemplate $template
     * @param array $variables
     * @return array{subject: string, body_html: string, body_text: string|null}
     */
    public function render(EmailTemplate $template, array $variables = []): array
    {
        return [
            'subject' => $this->renderString($template->subject, $variables),
            'body_html' => $this->renderString($template->body_html, $variables),
            'body_text' => $template->body_text ? $this->renderString($template->body_text, $variables) : null,
        ];
    }
}
