<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace RazeSoldier\MWOpsTool;

use Curl\Curl;

/**
 * Used to query data from the wiki API
 * @package RazeSoldier\MWOpsTool
 */
class Queryer
{
    /**
     * @var Curl
     */
    private $curl;

    private $endpoint;

    private $option;

    /**
     * @var string The symbol of reminder continue in API
     */
    private $continueSign;

    /**
     * @var string The token that continue to query
     */
    private $continueToken;

    /**
     * Queryer constructor.
     * @param string $endpoint The wiki API endpoint
     * @param array $option
     * @param string $continueSign The symbol of reminder continue in API
     * @throws \ErrorException
     */
    public function __construct(string $endpoint, array $option, string $continueSign)
    {
        $this->curl = new Curl;
        $this->endpoint = $endpoint;
        $this->option = $option;
        $this->continueSign = $continueSign;
    }

    public function query()
    {
        $option = $this->option;
        if ($this->canContinue()) {
            $option[$this->continueSign] = $this->continueToken;
        }
        $res = json_decode(json_encode($this->curl->get($this->endpoint, $option)), true);
        if (isset($res['continue'])) {
            $this->continueToken = $res['continue'][$this->continueSign];
        } else {
            $this->continueToken = null;
        }
        return $res;
    }

    /**
     * Can I continue to query?
     * @return bool
     */
    public function canContinue() : bool
    {
        return $this->continueToken !== null;
    }
}