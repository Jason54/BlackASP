<?php

namespace BlackTeam\Blackasp;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class ProfanityFilter {

    public $plugin;

    const SEPARATOR_PLACEHOLDER = '{!!}';

    protected $escapedSeparatorCharacters = array(
        '\s',
    );

    protected $separatorCharacters = array(
        '@',
        '#',
        '%',
        '&',
        '_',
        ';',
        "'",
        '"',
        ',',
        '~',
        '`',
        '|',
        '!',
        '$',
        '^',
        '*',
        '(',
        ')',
        '-',
        '+',
        '=',
        '{',
        '}',
        '[',
        ']',
        ':',
        '<',
        '>',
        '?',
        '.',
        '/',
    );

    protected $characterSubstitutions = array(
        '/a/' => array(
            'a',
            '4',
            '@',
            'Á',
            'á',
            'À',
            'Â',
            'à',
            'Â',
            'â',
            'Ä',
            'ä',
            'Ã',
            'ã',
            'Å',
            'å',
            'æ',
            'Æ',
            'α',
            'Δ',
            'Λ',
            'λ',
        ),
        '/b/' => array('b', '8', '\\', '3', 'ß', 'Β', 'β'),
        '/c/' => array('c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'),
        '/d/' => array('d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'),
        '/e/' => array('e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'),
        '/f/' => array('f', 'ƒ'),
        '/g/' => array('g', '6', '9'),
        '/h/' => array('h', 'Η'),
        '/i/' => array('i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'),
        '/j/' => array('j'),
        '/k/' => array('k', 'Κ', 'κ'),
        '/l/' => array('l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'),
        '/m/' => array('m'),
        '/n/' => array('n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'),
        '/o/' => array(
            'o',
            '0',
            'Ο',
            'ο',
            'Φ',
            '¤',
            '°',
            'ø',
            'ô',
            'Ô',
            'ö',
            'Ö',
            'ò',
            'Ò',
            'ó',
            'Ó',
            'œ',
            'Œ',
            'ø',
            'Ø',
            'ō',
            'Ō',
            'õ',
            'Õ',
        ),
        '/p/' => array('p', 'ρ', 'Ρ', '¶', 'þ'),
        '/q/' => array('q'),
        '/r/' => array('r', '®'),
        '/s/' => array('s', '5', '$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'),
        '/t/' => array('t', 'Τ', 'τ'),
        '/u/' => array('u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'),
        '/v/' => array('v', 'υ', 'ν'),
        '/w/' => array('w', 'ω', 'ψ', 'Ψ'),
        '/x/' => array('x', 'Χ', 'χ'),
        '/y/' => array('y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'),
        '/z/' => array('z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'),
    );

    protected $profanities = [];
    protected $separatorExpression;
    protected $characterExpressions;

    public function __construct(BlackASP $plugin) {
        $this->plugin = $plugin;
        $this->profanities = (new Config($this->plugin->getDataFolder() . "swearwords.yml"))->getAll()["swearwords"];

        $this->separatorExpression = $this->generateSeparatorExpression();
        $this->characterExpressions = $this->generateCharacterExpressions();
    }
    public function hasProfanity($string) {
        if (empty($string)) {
            return false;
        }

        $profanities = [];
        $profanityCount = count($this->profanities);

        for ($i = 0; $i < $profanityCount; $i++) {
            $profanities[$i] = $this->generateProfanityExpression(
                    $this->profanities[$i], $this->characterExpressions, $this->separatorExpression
            );
        }

        foreach ($profanities as $profanity) {
            if ($this->stringHasProfanity($string, $profanity)) {
                return true;
            }
        }

        return false;
    }
    public function obfuscateIfProfane($string) {
        if ($this->hasProfanity($string)) {
            $string = str_repeat("*", strlen($string));
        }

        return $string;
    }
    protected function generateProfanityExpression($word, $characterExpressions, $separatorExpression) {
        $expression = '/' . preg_replace(
                        array_keys($characterExpressions), array_values($characterExpressions), $word
                ) . '/i';

        return str_replace(self::SEPARATOR_PLACEHOLDER, $separatorExpression, $expression);
    }
    private function stringHasProfanity($string, $profanity) {
        return preg_match($profanity, $string) === 1;
    }
    private function generateEscapedExpression(
    array $characters = array(), array $escapedCharacters = array(), $quantifier = '*?'
    ) {
        $regex = $escapedCharacters;
        foreach ($characters as $character) {
            $regex[] = preg_quote($character, '/');
        }

        return '[' . implode('', $regex) . ']' . $quantifier;
    }
    private function generateSeparatorExpression() {
        return $this->generateEscapedExpression($this->separatorCharacters, $this->escapedSeparatorCharacters);
    }
    protected function generateCharacterExpressions() {
        $characterExpressions = array();
        foreach ($this->characterSubstitutions as $character => $substitutions) {
            $characterExpressions[$character] = $this->generateEscapedExpression(
                            $substitutions, array(), '+?'
                    ) . self::SEPARATOR_PLACEHOLDER;
        }

        return $characterExpressions;
    }
}
