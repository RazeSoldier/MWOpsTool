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

namespace RazeSoldier\MWOpsTool\Command;

use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface
};
use Symfony\Component\Process\Process;

/**
 * Used to batch update extensions and skins via Git
 * @package RazeSoldier\MWOpsTool\Command
 */
class UpdateExtension extends Command
{
    const UPDATE_ITEM = ['extensions', 'skins'];

    protected static $defaultName = 'update:extension';

    protected function configure()
    {
        $this->setDescription('Batch update extensions and skins via Git');
        $this->addArgument('dir', InputArgument::REQUIRED, 'The wiki root directory path');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->checkGitBin();
        } catch (\RuntimeException $e) {
            $output->writeln('Error: ' . $e->getMessage());
            return;
        }

        /** @var array $dirs Store all entries */
        $dirs = [];
        foreach (self::UPDATE_ITEM as $item) {
            $dir = $input->getArgument('dir') . "/$item";
            try {
                $dirIterator = new \DirectoryIterator($dir);
            } catch (\UnexpectedValueException $e) {
                $output->writeln("Error: Failed to open $dir");
                return;
            }
            foreach ($dirIterator as $subIterator) {
                if ($subIterator->isDot()) {
                    continue;
                }
                if (!$subIterator->isDir()) {
                    continue;
                }
                $dirs[] = $subIterator->getRealPath();
            }
        }

        if ($dirs === []) {
            $output->writeln('Nothing needs to be updated');
            return;
        }

        foreach ($dirs as $dir) {
            $this->doUpdate($dir);
        }
    }

    /**
     * Checks if `git` can be find in $PATH
     * @throws \RuntimeException
     */
    private function checkGitBin()
    {
        exec('git --version', $output, $code);
        if ($code !== 0) {
            throw new \RuntimeException('Git binary is not in $PATH');
        }
    }

    /**
     * @param string $path
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function doUpdate(string $path)
    {
        chdir($path);
        $cmd = ['git', 'pull'];
        $process = new Process($cmd);
        $process->setTimeout(null);
        echo '> ' . $path . "\n";
        $process->run();
        if (!$process->isSuccessful()) {
            echo $process->getErrorOutput() . "\n";
        }
    }
}
