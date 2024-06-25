<?php

declare (strict_types=1);
namespace Rector\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
final class StmtMatcher
{
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    public function __construct(BetterNodeFinder $betterNodeFinder)
    {
        $this->betterNodeFinder = $betterNodeFinder;
    }
    /**
     * @param \PhpParser\Node|mixed[] $stmt
     */
    public function matchFuncCallNamed($stmt, string $functionName) : ?FuncCall
    {
        /** @var FuncCall[] $funcCalls */
        $funcCalls = $this->betterNodeFinder->findInstancesOf($stmt, [FuncCall::class]);
        foreach ($funcCalls as $funcCall) {
            if (!$funcCall->name instanceof Name) {
                continue;
            }
            if ($funcCall->name->toString() !== $functionName) {
                continue;
            }
            return $funcCall;
        }
        return null;
    }
}
