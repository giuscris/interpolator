<?php

namespace Interpolator;

use Interpolator\Errors\SyntaxError;

class Tokenizer
{
    /**
     * Regex matching identifier tokens
     */
    protected const IDENTIFIER_REGEX = '/[A-Za-z_][A-Za-z0-9_]*/A';

    /**
     * Regex matching number tokens
     */
    protected const NUMBER_REGEX = '/[+-]?[0-9]+(.[0-9]+)?([Ee][+-]?[0-9]+)?/A';

    /**
     * Regex matching single quote string tokens
     */
    protected const SINGLE_QUOTE_STRING_REGEX = '/\'(?:[^\'\\\\]|\\\\.)*\'/A';

    /**
     * Regex matching double quote string tokens
     */
    protected const DOUBLE_QUOTE_STRING_REGEX = '/"(?:[^"\\\\]|\\\\.)*"/A';

    /**
     * Punctuation characters
     */
    protected const PUNCTUATION_CHARACTERS = '.,()[]';

    /**
     * Arrow sequence
     */
    protected const ARROW_SEQUENCE = '=>';

    /**
     * Tokenizer input string
     */
    protected string $input;

    /**
     * Tokenizer input length
     */
    protected int $length = 0;

    /**
     * Current position within the input string
     */
    protected int $position = 0;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->length = strlen($input);
    }

    /**
     * Tokenize input
     */
    public function tokenize(): TokenStream
    {
        $tokens = [];

        while ($this->position < $this->length) {
            switch (true) {
                case preg_match(self::IDENTIFIER_REGEX, $this->input, $matches, 0, $this->position):
                    $value = array_shift($matches);
                    $tokens[] = new Token(Token::TYPE_IDENTIFIER, $value, $this->position);
                    $this->position += strlen($value);
                    break;

                case preg_match(self::NUMBER_REGEX, $this->input, $matches, 0, $this->position):
                    $value = array_shift($matches);
                    $tokens[] = new Token(Token::TYPE_NUMBER, $value, $this->position);
                    $this->position += strlen($value);
                    break;

                case preg_match(self::SINGLE_QUOTE_STRING_REGEX, $this->input, $matches, 0, $this->position):
                case preg_match(self::DOUBLE_QUOTE_STRING_REGEX, $this->input, $matches, 0, $this->position):
                    $value = array_shift($matches);
                    $tokens[] = new Token(Token::TYPE_STRING, $value, $this->position);
                    $this->position += strlen($value);
                    break;

                case substr($this->input, $this->position, strlen(self::ARROW_SEQUENCE)) === self::ARROW_SEQUENCE:
                    $tokens[] = new Token(Token::TYPE_ARROW, self::ARROW_SEQUENCE, $this->position);
                    $this->position += strlen(self::ARROW_SEQUENCE);
                    break;
                
                case strpos(self::PUNCTUATION_CHARACTERS, $this->input[$this->position]) !== false:
                    $tokens[] = new Token(Token::TYPE_PUNCTUATION, $this->input[$this->position], $this->position);
                    $this->position++;
                    break;

                case ctype_space($this->input[$this->position]):
                    $this->position++;
                    break;

                default:
                    throw new SyntaxError(sprintf('Unexpected character "%s" at position %d', $this->input[$this->position], $this->position));
            }
        }

        $tokens[] = new Token(Token::TYPE_END, null, $this->position);

        return new TokenStream($tokens);
    }

    /**
     * Tokenize a string
     */
    public static function tokenizeString(string $string): TokenStream
    {
        $tokenizer = new static($string);
        return $tokenizer->tokenize();
    }
}
