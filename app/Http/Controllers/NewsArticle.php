<?php

namespace App\Http\Controllers;

class NewsArticle
{
    protected $title = '';
    protected $subtitle = '';
    protected $editor = '';
    protected $body = '';
    protected $media = '';

    /**
     * Initializes the article instance.
     *
     * @param string $title
     * @param string $subtitle
     * @param string $editor
     * @param string $body
    //  * @param string $media
     */
    public function __construct($title, $subtitle = null, $editor = null, $body) {
        $this->title        = $title;
        $this->subtitle     = $subtitle;
        $this->editor       = $editor;
        $this->body         = $body;
        // $this->media        = $media;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function title() {
        return $this->title;
    }

    /**
     * Returns the subtitle.
     *
     * @return string
     */
    public function subtitle() {
        return $this->subtitle;
    }

    /**
     * Returns the editor.
     *
     * @return string
     */
    public function editor() {
        return $this->editor;
    }

    /**
     * Returns the body.
     *
     * @return string
     */
    public function body() {
        return $this->body;
    }

    /**
     * Returns the media.
     *
     * @return string
     */
    public function media() {
        return $this->media;
    }
}
