<?php
namespace Layerok\Restapi\Classes;

// Convert an UTF-8 encoded string to a single-byte string suitable for
// functions such as levenshtein.
//
// The function simply uses (and updates) a tailored dynamic encoding
// (in/out map parameter) where non-ascii characters are remapped to
// the range [128-255] in order of appearance.
//
// Thus it supports up to 128 different multibyte code points max over
// the whole set of strings sharing this encoding.
//
function utf8_to_extended_ascii($str, &$map)
{
    // find all multibyte characters (cf. utf-8 encoding specs)
    $matches = array();
    if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches))
        return $str; // plain ascii string

    // update the encoding map with the characters not already met
    foreach ($matches[0] as $mbc)
        if (!isset($map[$mbc]))
            $map[$mbc] = chr(128 + count($map));

    // finally remap non-ascii characters
    return strtr($str, $map);
}

// Didactic example showing the usage of the previous conversion function but,
// for better performance, in a real application with a single input string
// matched against many strings from a database, you will probably want to
// pre-encode the input only once.
//
function levenshtein_utf8($s1, $s2) :int
{
    $charMap = array();
    $s1 = utf8_to_extended_ascii($s1, $charMap);
    $s2 = utf8_to_extended_ascii($s2, $charMap);

    return levenshtein($s1, $s2);
}


class FuzzySearch {

    public $searched_sentence;
    private $caseInsensitive = false;
    private $ignoreWordLength = 0;
    private $suggestionDistance = 1;

    public function __construct($searched_sentence, $caseInsensitive = false)
    {
        $this->caseInsensitive = $caseInsensitive;
        $this->searched_sentence = new Sentence($this->checkCase($searched_sentence));
    }

    public function search($sentence): Sentence {
        $sentence = new Sentence($this->checkCase($sentence));
        $sentence->setIgnoreWordLength($this->ignoreWordLength);
        $sentence->setSuggestionDistance($this->suggestionDistance);
        $this->searched_sentence->setIgnoreWordLength($this->ignoreWordLength);

        return $sentence->fuzzy($this->searched_sentence);

    }

    public function checkCase($str) {
        return $this->caseInsensitive ? mb_strtolower($str) : $str;
    }

    public function setCaseInsensitive($state) {
        $this->caseInsensitive = $state;
    }

    public function setSuggestionDistance($distance) {
        $this->suggestionDistance = $distance;
    }

    public function setIgnoreWordsWithLengthLessThan($len) {
        $this->ignoreWordLength = $len;
    }
}

class Sentence {
    private $str;
    private $ignoreWordLength = 0;
    private $wordsCache = [];
    private $suggestionDistance = 1;

    public function __construct($str) {
        $this->str = $str;
    }

    public function setSuggestionDistance($distance) {
        $this->suggestionDistance = $distance;
    }

    public function setIgnoreWordLength($len) {
        // clearing cache
        $this->wordsCache = [];
        $this->ignoreWordLength = $len;
    }

    public function words(): array {
        if(count($this->wordsCache) > 0) {
            return $this->wordsCache;
        }

        foreach ($this->explode($this->str) as $word) {
            $wordClass = new Word($word);
            if($wordClass->length() < $this->ignoreWordLength) {
                // ignoring words with length 1 or 2
                continue;
            }
            $this->wordsCache[] = $wordClass;
        }
        return $this->wordsCache;
    }

    function explode($sentence) {
        return explode(' ', preg_replace('/\s+/', ' ', trim($sentence)));
    }

    public function fuzzy(Sentence $sentence): Sentence {
        foreach ($this->words() as $word) {
            foreach ($sentence->words() as $searched_word) {
                $distance = $searched_word->fuzzy($word->getStr());

                // not sure if I need to suggest word if it contains searched substring
                if($distance <= $this->suggestionDistance || str_contains($word->getStr(), $searched_word->getStr())) {
                    $suggestion = new Suggestion($word->getStr(), $distance);
                    $searched_word->addSuggestion($suggestion);
                }
            }
        }

        return $sentence;

    }

    public function getStr() {
        return $this->str;
    }
}

class Word {
    private $str;
    private $suggestions = [];
    private $cacheLength;

    public function __construct($str) {
        $this->str = $str;
    }

    public function length(): int {
        if(!is_null($this->cacheLength)) {
            return $this->cacheLength;
        }
        $arr = [];
        $this->cacheLength = strlen(utf8_to_extended_ascii($this->getStr(),$arr));
        return $this->cacheLength;
    }

    public function fuzzy($str): int {
        return levenshtein_utf8($this->str, $str);
    }

    public function addSuggestion($suggestion) {
        $this->suggestions[] = $suggestion;
    }

    public function getSuggestions(): array {
        return $this->suggestions;
    }

    public function getStr(): string {
        return $this->str;
    }

    public function setStr($str) {
        // clearing cache
        $this->cacheLength = null;
        $this->str = $str;
    }

}

class Suggestion extends Word {
    public $distance;
    public function __construct($str, $distance)
    {
        parent::__construct($str);
        $this->distance = $distance;
    }

}
