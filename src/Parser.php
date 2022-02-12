<?php

namespace Interpolator;

use Interpolator\Errors\SyntaxError;
use Interpolator\Nodes\AbstractNode;
use Interpolator\Nodes\ArgumentsNode;
use Interpolator\Nodes\ArrayKeysNode;
use Interpolator\Nodes\ArrayNode;
use Interpolator\Nodes\IdentifierNode;
use Interpolator\Nodes\ImplicitArrayKeyNode;
use Interpolator\Nodes\NumberNode;
use Interpolator\Nodes\StringNode;

class Parser
{
    protected TokenStream $stream;

    public function __construct(TokenStream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Parse the tokens
     */
    public function parse(): AbstractNode
    {
        $node = $this->parseIdentifierToken();
        $this->stream->expectEnd();
        return $node;
    }

    /**
     * Parse a given TokenStream object
     */
    public static function parseTokenStream(TokenStream $stream): AbstractNode
    {
        $parser = new static($stream);
        return $parser->parse();
    }

    /**
     * @return IdentifierNode
     */
    protected function parseIdentifierToken(): IdentifierNode
    {
        $token = $this->stream->expect(Token::TYPE_IDENTIFIER);

        $traverse = null;

        $arguments = null;

        if ($this->stream->current()->test(Token::TYPE_PUNCTUATION, '(')) {
            $arguments = $this->parseArguments();
        }

        if ($this->stream->current()->test(Token::TYPE_PUNCTUATION, '.')) {
            $traverse = $this->parseDotNotation();
        }

        if ($this->stream->current()->test(Token::TYPE_PUNCTUATION, '[')) {
            $traverse = $this->parseBracketsNotation();
        }

        return new IdentifierNode($token->value(), $arguments, $traverse);
    }

    protected function parseNumberToken(): NumberNode
    {
        $token = $this->stream->expect(Token::TYPE_NUMBER);
        return new NumberNode($token->value() + 0);
    }

    protected function parseStringToken(): StringNode
    {
        $token = $this->stream->expect(Token::TYPE_STRING);
        return new StringNode(stripcslashes(trim($token->value(), '\'"')));
    }

    protected function parseDotNotation(): IdentifierNode
    {
        $this->stream->expect(Token::TYPE_PUNCTUATION, '.');
        return $this->parseIdentifierToken(false);
    }

    protected function parseBracketsNotation(): AbstractNode
    {
        $this->stream->expect(Token::TYPE_PUNCTUATION, '[');

        $token = $this->stream->current();

        switch ($token->type()) {
            case Token::TYPE_NUMBER:
                $key = $this->parseNumberToken();
                break;

            case Token::TYPE_STRING:
                $key = $this->parseStringToken();
                break;

            default:
                throw new SyntaxError(sprintf('Unexpected %s at position %d', $token, $token->position()));
        }

        $this->stream->expect(Token::TYPE_PUNCTUATION, ']');

        return $key;
    }

    protected function parseArguments(): ArgumentsNode
    {
        $this->stream->expect(Token::TYPE_PUNCTUATION, '(');

        $arguments = [];

        while (!$this->stream->current()->test(Token::TYPE_PUNCTUATION, ')')) {
            if ($arguments !== []) {
                $this->stream->expect(Token::TYPE_PUNCTUATION, ',');
            }
            $arguments[] = $this->parseExpression();
        }

        $this->stream->expect(Token::TYPE_PUNCTUATION, ')');

        return new ArgumentsNode($arguments);
    }

    protected function parseExpression(): AbstractNode
    {
        $token = $this->stream->current();

        switch ($token->type()) {
            case Token::TYPE_IDENTIFIER:
                return $this->parseIdentifierToken();

            case Token::TYPE_NUMBER:
                return $this->parseNumberToken();

            case Token::TYPE_STRING:
                return $this->parseStringToken();

            case Token::TYPE_PUNCTUATION:
                if ($token->value() === '[') {
                    return $this->parseArrayExpression();
                }
                // no break for other punctuation characters

            default:
                throw new SyntaxError(sprintf('Unexpected %s at position %d', $token, $token->position()));
        }
    }

    protected function parseArrayExpression(): ArrayNode
    {
        $this->stream->expect(Token::TYPE_PUNCTUATION, '[');

        $elements = [];

        $keys = [];

        while (!$this->stream->current()->test(Token::TYPE_PUNCTUATION, ']')) {
            if ($elements !== []) {
                $this->stream->expect(Token::TYPE_PUNCTUATION, ',');
            }

            $value = $this->parseExpression();

            if ($this->stream->current()->test(Token::TYPE_ARROW)) {
                $arrow = $this->stream->consume();

                if ($value->type() === ArrayNode::TYPE) {
                    throw new SyntaxError(sprintf('Unexpected %s at position %d', $arrow, $arrow->position()));
                }

                $key = $value;
                $value = $this->parseExpression();
            } else {
                $key = new ImplicitArrayKeyNode();
            }

            $elements[] = $value;
            $keys[] = $key;
        }

        $this->stream->expect(Token::TYPE_PUNCTUATION, ']');

        return new ArrayNode($elements, new ArrayKeysNode($keys));
    }
}
