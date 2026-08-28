<?php

namespace QUI\AI\MCP;

use Mcp\Schema\Result\CallToolResult;

if (!class_exists(ToolHelper::class)) {
    class ToolHelper
    {
        public static function parseExceptionToResult(mixed $Exception): CallToolResult
        {
            $message = $Exception instanceof \Throwable
                ? $Exception::class . ': ' . $Exception->getMessage()
                : 'Unknown MCP tool error';

            return new CallToolResult([$message], true);
        }
    }
}
