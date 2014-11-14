<?php
namespace sqlparserunit;

use Intersvyaz\SqlParser\Parser;

class SqlParserTest extends TestCase
{
    public function sqlData()
    {
        $query = '/*param1 sql1 */
                /*param2 sql2 */
                --*param3 sql3
                --*param6 --*param7 sql7
                /*param4 --*param5 sql5 */
                /*param8 --*param9 --*param10 sql10 */
                --*param11 :@param11
        ';

        return [
            [$query, ['param1' => 'v1'], '/^\s*sql1\s*$/'],
            [$query, ['param1' => 'v1', 'param2' => ['v2', \PDO::PARAM_STR]], '/^\s*sql1\s*sql2\s*$/'],
            [$query, ['param6' => 'v6'], '/^\s*$/'],
            [$query, ['param6' => 'v6', 'param7' => 'v7'], '/^\s*sql7\s*$/'],
            [$query, ['param4' => 'v4', 'param5' => 'v5'], '/^\s*sql5\s*$/'],
            [$query, ['param8' => 'v8', 'param9' => 'v9', 'param10' => 'v10'], '/^\s*sql10\s*$/'],
            ["sql1\n\n\n\n\nsql2", ['param1' => 'v1'], '/^sql1\nsql2$/'],
            ["sql1", [], '/^sql1$/'],
            ["-- test\n".$query, [], '/^-- test$/'],
            [$query, ['param11' => [['v1', 'v2']]], "/^:param11_0,:param11_1$/"],
            ["--*param order by param", ['param' => ['v1', 'bind' => 'text']], "/^order by v1$/"],

        ];
    }

    /**
     * @dataProvider sqlData
     */
    public function testSqlParsing($query, $params, $queryPattern)
    {
        $this->assertRegExp($queryPattern, (string)(new Parser($query, $params)));
    }

    public function paramsData()
    {
        return [
            [[], []],
            [[':simpleName' => 'simpleValue'], [':simpleName' => 'simpleValue']],
            [[':simpleNameSimpleValueWithType' => ['simpleValue', \PDO::PARAM_STR]], [':simpleNameSimpleValueWithType' => ['simpleValue', \PDO::PARAM_STR]]],
            [[':complexNameSimpleValue' => ['simpleValue', 'bind' => true]], [':complexNameSimpleValue' => 'simpleValue']],
            [[':complexNameBindText' => ['simpleValue', 'bind' => 'text']], []],
            [[':complexNameNoBind' => ['bind' => false]], []],
            [['arrayName' => [[0, 1, 2, 3]]], [':arrayName_0' => 0, ':arrayName_1' => 1, ':arrayName_2' => 2, ':arrayName_3' => 3]],
        ];
    }

    /**
     * @dataProvider paramsData
     */
    public function testSimplifyParams($params, $simplifiedParams)
    {
        $parser = new Parser('', $params);
        $this->assertEquals($simplifiedParams, $parser->getSimplifiedParams());
    }
}