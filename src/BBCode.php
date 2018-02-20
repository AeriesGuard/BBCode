<?php

namespace AeriesGuard;

use AeriesGuard\Token\RootToken;
use const AeriesGuard\Token\TOKEN_ENDTAG;
use const AeriesGuard\Token\TOKEN_ROOT;
use const AeriesGuard\Token\TOKEN_TAG;
use const AeriesGuard\Token\TOKEN_TEXT;

class BBCode
{
    private $root;
    private $result = "";
    private $paraOpened;
    private $preOpened;
    private $refusePara;
    private $previousToken;
    private $currentPage = 1;

    public function debug(Token\StringToken $token)
    {
        echo "<blockquote>";
        switch ($token->getType()) {
            case TOKEN_ROOT:
                echo "ROOT";
                break;
            case TOKEN_TAG:
                echo "TAG => " . $token->GetName();
                foreach ($token->attributes as $key => $value) {
                    echo "<p><em>" . $key . " => " . $value . "</em></p>";
                }
                break;
            case TOKEN_TEXT:
                echo $token->newPara ? "<p>TEXT <em>New Para</em></p>" : "<p>TEXT</p>";
                echo "<pre>" . var_export($token->getText(), true) . "</pre>"; //.str_replace("\n", "<br/>", $token->GetText());
                break;
        }

        foreach ($token as $t) {
            $this->debug($t);
        }

        echo "</blockquote>";

        switch ($token->getType()) {
            case TOKEN_ENDTAG:
                echo "ENDTAG => " . $token->GetName();
                break;
        }
    }

    public function getRootToken($text)
    {
        return new RootToken($text);
    }

    public function getText($text)
    {
        $this->root = new RootToken($text);
        $this->result = "";
        $this->paraOpened = TRUE;
        $this->preOpened = FALSE;
        $this->refusePara = [];
        $this->previousToken = NULL;
        $this->doToken($this->root, TRUE);
        return $this->result;
    }

    public function getHtml($text)
    {
        $this->root = new RootToken($text);
        $this->result = "";
        $this->paraOpened = FALSE;
        $this->preOpened = FALSE;
        $this->refusePara = [];
        $this->previousToken = NULL;
        $this->doToken($this->root);
        return $this->result;
    }

    private function appendText($text)
    {
        if ($this->preOpened) {
            $this->result .= $text;
        } else {
            //$this->result .= parse_smileys($text, base_url('assets/img/smileys'));
            $this->result .= $text;
        }
    }

    private function isParaAccepted()
    {
        if ($this->refusePara != NULL && count($this->refusePara) > 0) {
            return $this->refusePara[count($this->refusePara) - 1];
        } else {
            return TRUE;
        }
    }

    private function refusePara()
    {
        array_push($this->refusePara, FALSE);
    }

    private function acceptPara()
    {
        if (count($this->refusePara) > 0) {
            array_pop($this->refusePara);
        }
    }

    private function ensurePara()
    {
        if ($this->isParaAccepted() && !$this->paraOpened) {
            $this->appendText("\n<p>");
            //$this->result .= "\n<p>";
            $this->paraOpened = TRUE;
        } else if ($this->isParaAccepted() && $this->paraOpened && $this->previousToken && $this->previousToken->getType() == TOKEN_TEXT && $this->previousToken->newPara && trim($this->previousToken->getText(), ' ') == '') {
            $this->breakPara();
            $this->appendText("\n<p>");
            $this->paraOpened = TRUE;
        }
    }

    private function breakPara()
    {
        if ($this->paraOpened) {
            $this->appendText("</p>\n");
            $this->paraOpened = FALSE;
        }
    }

    private function doToken(Token\StringToken $token, $nopara = FALSE)
    {
        switch ($token->getType()) {
            case TOKEN_ROOT:
                foreach ($token as $child) {
                    $this->doToken($child);
                }

                if (!$nopara) {
                    $this->breakPara();
                }
                break;
            case TOKEN_TAG:
                if ($this->doTag($token)) {
                    foreach ($token as $child) {
                        $this->doToken($child);
                    }
                }
                break;
            case TOKEN_TEXT:
                $this->doText($token);
                break;
            case TOKEN_ENDTAG:
                $this->doEndTag($token);
                break;
        }
        $this->previousToken = $token;
    }

    private function doText(Token\StringToken $token)
    {
        $text = $token->getText();
        if ($this->preOpened) {
            if ($token->newPara) {
                $this->appendText("\n\n");
            }
            $this->appendText($text);
        }else {
            if ($token->newPara) {
                $this->breakPara();
            }

            if ($text != '') {
                $this->ensurePara();
            }

            if (isset($this->previousToken->block) && !$this->previousToken->block) {
                $this->appendText(str_replace("\n", "<br/>", $text));
            } elseif (trim($text, ' ') != "") {
                $this->appendText(str_replace("\n", "<br/>", ltrim($text, "\n")));
            }
        }
    }

    private function doTag(Token\StringToken $token)
    {
        $doChildren = TRUE;
        if ($this->preOpened) {
            $this->doTagDefault($token);
        } else {
            switch ($token->GetName()) {
                case "b":
                    $this->ensurePara();
                    $this->appendText('<strong>');
                    $this->refusePara();
                    break;
                case "i":
                    $this->ensurePara();
                    $this->appendText('<em>');
                    $this->refusePara();
                    break;
                case "u":
                    $this->ensurePara();
                    $this->appendText('<span class="underline">');
                    $this->refusePara();
                    break;
                case "s":
                    $this->ensurePara();
                    $this->appendText('<del>');
                    $this->refusePara();
                    break;
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                    $this->breakPara();
                case 'tr':
                case 'th':
                case 'td':
                    $this->appendText('<' . $token->GetName() . '>');
                    $this->refusePara();
                    break;
                case 'sub':
                case 'sup':
                    $this->ensurePara();
                    $this->appendText('<' . $token->GetName() . '>');
                    $this->refusePara();
                    break;
                case 'table':
                    $this->breakPara();
                    $this->appendText('<' . $token->GetName() . ' class="table table-striped table-hover table-condensed table-bordered">');
                    $this->refusePara();
                    break;
                case "more":
                    $this->breakPara();
                    $this->appendText('<hr/>');
                    $doChildren = FALSE;
                    break;
                case "page":
                    $this->breakPara();
                    $this->appendText('<hr id="page-' . $this->currentPage . '"/>');
                    $this->currentPage++;
                    $doChildren = FALSE;
                    break;
                case "url":
                    $doChildren = $this->doLink($token);
                    break;
                case "anchor":
                    $this->doAnchor($token);
                    $doChildren = FALSE;
                    break;
                case "code":
                    $this->ensurePara();
                    $this->appendText('<code>');
                    $this->preOpened = 'code';
                    break;
                case "img":
                    $this->doImage($token);
                    $doChildren = FALSE;
                    break;
                case "quote":
                    $this->doQuote($token);
                    break;
                case "script":
                    $this->doScript($token);
                    break;
                case "spoiler":
                    $this->doSpoiler();
                    break;
                case 'list':
                    $this->breakPara();
                    $this->appendText('<ul>');
                    $this->refusePara();
                    break;
                case 'olist':
                    $this->breakPara();
                    if (isset($token->attributes['s'])) {
                        $start = $token->attributes['s'];
                        $this->appendText('<ol start="' . $start . '">');
                    } else {
                        $this->appendText('<ol>');
                    }
                    $this->refusePara();
                    break;
                case 'li':
                    $this->appendText('<li>');
                    break;
                case "video":
                    $doChildren = $this->doVideo($token);
                    break;
                default:
                    $this->doTagDefault($token);
                    break;
            }

            switch ($token->GetName()) {
                case "b":
                case "i":
                case "u":
                case "s":
                case 'sub':
                case 'sup':
                case "url":
                case "code":
                    $token->block = false;
                    break;
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                case 'table':
                case 'tr':
                case 'th':
                case 'td':
                case "quote":
                case "review":
                case "script":
                case "spoiler":
                case 'list':
                case 'olist':
                case 'li':
                case 'video':
                default:
                    $token->block = true;
                    break;
            }
        }
        return $doChildren;
    }

    private function doEndTag(Token\StringToken $token)
    {
        if ($this->preOpened && $token->GetName() != $this->preOpened) {
            $this->doEndTagDefault($token);
        } else {
            switch ($token->GetName()) {
                case "b":
                    $this->appendText('</strong>');
                    $this->acceptPara();
                    break;
                case "i":
                    $this->appendText('</em>');
                    $this->acceptPara();
                    break;
                case "u":
                    $this->appendText('</span>');
                    $this->acceptPara();
                    break;
                case "s":
                    $this->appendText('</del>');
                    $this->acceptPara();
                    break;
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                case 'table':
                case 'tr':
                case 'th':
                case 'td':
                case 'sub':
                case 'sup':
                    $this->appendText('</' . $token->GetName() . '>');
                    $this->acceptPara();
                    break;
                case "url":
                    $this->appendText('</a>');
                    $this->acceptPara();
                    break;
                case "code":
                    $this->appendText('</code>');
                    $this->preOpened = FALSE;
                    break;
                case "quote":
                    $this->breakPara();
                    $this->appendText("</blockquote>");
                    break;
                case "review":
                    $this->breakPara();
                    $this->appendText("</div></div>");
                    break;
                case "script":
                    $this->appendText('</pre>');
                    $this->acceptPara();
                    $this->preOpened = FALSE;
                    break;
                case "spoiler":
                    $this->appendText("</div>\n</div>");
                    break;
                case 'list':
                    $this->appendText('</ul>');
                    $this->acceptPara();
                    break;
                case 'olist':
                    $this->appendText('</ol>');
                    $this->acceptPara();
                    break;
                case 'li':
                    $this->appendText('</li>');
                    break;
                case 'video':
                    break;
                default:
                    $this->doEndTagDefault($token);
                    break;
            }
        }
    }

    private function doTagDefault(Token\StringToken $token)
    {
        $this->appendText('[' . $token->GetName());
        foreach ($token->attributes as $key => $value) {
            if (isset($value)) {
                if ($key == "_default") {
                    $this->appendText("=" . $value);
                } else {
                    $this->appendText(" $key=$value");
                }
            } else {
                $this->appendText(" " . $key);
            }
        }

        $this->appendText(']');
    }

    private function doEndTagDefault(Token\StringToken $token)
    {
        $this->appendText('[/' . $token->GetName() . ']');
    }

    private function doAnchor(Token\StringToken $token)
    {
        $this->breakPara();
        $id = '';
        if ($token[0]->getType() == TOKEN_TEXT) {
            $id = $token[0]->getText();
        }

        $this->appendText("<a id=\"$id\"></a>");
    }

    private function doImage(Token\StringToken $token)
    {
        $this->breakPara();
        $url = '';
        if ($token[0]->getType() == TOKEN_TEXT) {
            $url = $token[0]->getText();
        } elseif (isset($token->attributes["_default"])) {
            $url = $token->attributes["_default"];
        }

        $align = $token->attributes["align"];
        $caption = NULL;
        if (isset($token->attributes["title"])) {
            $caption = $token->attributes["title"];
        } elseif (isset($token->attributes["caption"])) {
            $caption = $token->attributes["caption"];
        }

        if ($caption && !$align) {
            $align = 'center';
        }

        $str = '';

        if ($align == 'right' || $align == 'left') {
            $align = 'pull-' . $align;
        }

        if ($align) {
            $str = '<div class="' . $align . '">';
        }

        if ($align && $caption) {
            $str .= '<div class="content-view-embed">';
        }

        $str .= '<img src="' . $url . '" alt="" />';

        if ($caption) {
            $str .= '<span class="caption">' . $caption . '</span>';
        }

        if ($align) {
            $str .= '</div>';
        }

        if ($align && $caption) {
            $str .= '</div>';
        }

        $this->appendText($str);
    }

    private function doLink(Token\StringToken $token)
    {
        $doChildren = TRUE;
        $url = "#";
        $target = "";
        if (isset($token->attributes["url"])) {
            $url = $token->attributes["url"];
        } else if (isset($token->attributes["_default"])) {
            $url = $token->attributes["_default"];
        } else if ($token[0]->getType() == TOKEN_TEXT) {
            $url = $token[0]->getText();
            $doChildren = FALSE;
        }

        if (isset($token->attributes["t"])) {
            $target = $token->attributes["t"];
        }

        if (isset($token->attributes["target"])) {
            $target = $token->attributes["target"];
        }

        $embeddingImg = ($token[0]->getType() == TOKEN_TAG) && ($token[0]->GetName() == "img");

        if ($embeddingImg) {
            $this->breakPara();
        } else {
            $this->ensurePara();
        }

        $this->appendText("<a href=\"$url\"");
        if ($target) {
            $this->appendText(" target=\"$target\"");
        }

        $this->appendText(">");
        if ($embeddingImg) {
            $this->doImage($token[0]);
            $this->appendText("</a>");
            $doChildren = FALSE;
        } else if ($doChildren == FALSE) {
            $this->appendText("$url</a>");
        } else {
            $this->refusePara();
        }

        return $doChildren;
    }

    private function doQuote(Token\StringToken $token)
    {
        $this->breakPara();
        $cite = '';
        if (isset($token->attributes["cite"])) {
            $cite = $token->attributes["cite"];
        } else if (isset($token->attributes["_default"])) {
            $cite = $token->attributes["_default"];
        }

        if ($cite) {
            $this->appendText('<p class="cite">' . $cite . ':</p>');
        }

        $this->appendText("<blockquote>");
    }

    private function doScript(Token\StringToken $token)
    {
        $this->breakPara();
        $this->refusePara();
        $lang = $token->attributes["language"];
        $this->preOpened = 'script';
        $this->appendText('<pre class="brush:' . $lang . '">');
    }

    private function doVideo(Token\StringToken $token)
    {
        $this->breakPara();

        $type = $token->attributes["type"];

        if ($token[0]->getType() == TOKEN_TEXT) {
            $video = $token[0]->getText();
        }
        //www.youtube-nocookie.com/v/MN97Q9dk9Lo&hl=fr&fs=1&color1=0x5d1719&color2=0xcd311b&border=1
        //www.youtube.com/watch?v=MN97Q9dk9Lo

        $format = '';
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9-_]+)/i', $video, $result)) {
            $format = "youtube";
            $video_id = $result[1];
        } elseif (preg_match('#youtube-nocookie\.com/v/([a-zA-Z0-9-_]+)#i', $video, $result)) {
            $format = "youtube";
            $video_id = $result[1];
        } elseif (preg_match('#vimeo.com/([0-9]+)#i', $video, $result)) {
            $format = "vimeo";
            $video_id = $result[1];
        } elseif (preg_match('#dailymotion.com/video/([a-z0-9]+)_#i', $video, $result)) {
            $format = "dailymotion";
            $video_id = $result[1];
        }

        switch ($format) {
            case 'youtube': {
                if ($type == "sound" || $type == "audio") {
                    $this->appendText('<!-- @see http://www.labnol.org/internet/youtube-audio-player/26740/ -->
<div style="position:relative;width:640px;height:30px;overflow:hidden;">
<div style="position:absolute;top:-272px;left:-5px">
<iframe width="640" height="300"
  src="//www.youtube-nocookie.com/embed/' . $video_id . '?rel=0&modestbranding=1&fs=0">
</iframe>
</div>
</div>');
                } else {
                    $this->appendText('<div class="center"><iframe type="text/html" width="640" height="390"
src="//www.youtube-nocookie.com/embed/' . $video_id . '?rel=0"
frameborder="0" class="video"></iframe></div>');
                }
                break;
            }
            case 'vimeo': {
                $this->appendText('<div class="center">'
                . '<iframe src="//player.vimeo.com/video/' . $video_id . '" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'
                . '</div>');
                break;
            }
            case 'dailymotion': {
                $this->appendText('<div class="center">'
                . '<iframe frameborder="0" width="640" height="360" src="//www.dailymotion.com/embed/video/' . $video_id . '" allowfullscreen></iframe>'
                . '</div>');
                break;
            }
        }

        $this->acceptPara();
        return FALSE;
    }

    private function doSpoiler()
    {
        $this->breakPara();
        $this->appendText("<div class=\"spoiler degrade\">\n
<p class=\"title\"><span>Spoiler</span> (Sélectionnez le texte dans le cadre pointillé pour le faire apparaître)</p>\n
<div class=\"content\">\n");
    }
}
