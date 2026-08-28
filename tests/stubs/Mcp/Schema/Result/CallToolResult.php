<?php

namespace Mcp\Schema\Result;

if (!class_exists(CallToolResult::class)) {
    class CallToolResult
    {
        /**
         * @param array<int, mixed> $content
         */
        public function __construct(
            public readonly array $content = [],
            public readonly bool $isError = false
        ) {
        }
    }
}
