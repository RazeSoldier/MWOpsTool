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

use RazeSoldier\MWOpsTool\Command\BatchDeletePageByPattern;
use Symfony\Component\Console\Application;

class Kernel
{
    private $symfonyApp;

    public function __construct()
    {
        $this->symfonyApp = new Application();
        $this->symfonyApp->add(new BatchDeletePageByPattern());
    }

    public function run()
    {
        try {
            $this->symfonyApp->run();
        } catch (\Exception $e) {
            echo "Exception: {$e->getMessage()}\n";
            die(1);
        }
    }
}