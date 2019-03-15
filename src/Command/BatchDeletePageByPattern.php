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
use RazeSoldier\MWOpsTool\Queryer;
use mikehaertl\tmp\File;
use Symfony\Component\Process\{
    Process,
    Exception\ProcessFailedException
};

/**
 * Used to batch delete pages by pattern
 * This command using maintenance/deleteBatch.php to delete, not via API
 * But we using API to get title list, so require provide API endpoint
 * @package RazeSoldier\MWOpsTool\Command
 */
class BatchDeletePageByPattern extends Command
{
    protected static $defaultName = 'batch:delete-page-byPattern';

    protected function configure()
    {
        $this->setDescription('Batch delete pages by pattern');
        $this->addArgument('api-endpoint', InputArgument::REQUIRED, 'The wiki API endpoint URL');
        $this->addArgument('script_path', InputArgument::REQUIRED, 'maintenance/deleteBatch.php path');
        $this->addArgument('pattern', InputArgument::REQUIRED, 'The pattern of the title to delete the pages');
        $this->addArgument('username', InputArgument::OPTIONAL,
            'Username that will be shown in the log entries. If left empty, deletions will be attributed to the user called Delete page script.');
        $this->addArgument('reason', InputArgument::OPTIONAL, 'Reason for deletions. If empty, no reason will be shown in the logs.');
        $this->addArgument('namespace_id', InputArgument::OPTIONAL, 'The namespace ID' .
            ' that needs to be retrieved. If not provided, the default is 0', 0);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_readable($input->getArgument('script_path'))) {
            throw new \Exception('Failed to access the maintenance script');
        }
        $list = $this->getTitleList($input->getArgument('api-endpoint'), $input->getArgument('pattern'),
            $input->getArgument('namespace_id'));
        if ($list === []) {
            $output->writeln('No page will be delete');
            return;
        }
        $tmpFile = new File(implode("\n", $list));
        try {
            $this->runScript($input->getArgument('script_path'), $tmpFile->getFileName(), $input->getArgument('username')
                , $input->getArgument('result'));
        } catch (ProcessFailedException $e) {
            echo ">> ProcessFailedException:\n";
            echo $e->getProcess()->getErrorOutput() . "\n";
        }
    }

    /**
     * Run maintenance/deleteBatch.php
     * @param string $scriptPath
     * @param string $listFilePath
     * @param string|null $username
     * @param string|null $result
     * @throws ProcessFailedException
     */
    private function runScript(string $scriptPath, string $listFilePath, string $username = null, string $result = null)
    {
        $cmd = ['php', $scriptPath];
        if ($username !== null) {
            $cmd[] = '-u';
            $cmd[] = $username;
        }
        if ($result !== null) {
            $cmd[] = '-r';
            $cmd[] = $result;
        }
        $cmd[] = $listFilePath;
        $process = new Process($cmd);
        echo '> ' . $process->getCommandLine() . "\n";
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Get will delete page title list
     * @param string $endpoint
     * @param string $pattern
     * @param int $nsID The namespace ID that needs to be retrieved
     * @return array
     * @throws \ErrorException
     */
    private function getTitleList(string $endpoint, string $pattern, int $nsID) : array
    {
        $list = [];
        $queryer = new Queryer($endpoint, [
            'action' => 'query',
            'list' => 'allpages',
            'aplimit' => '500',
            'apnamespace' => $nsID,
            'format' => 'json',
            'formatversion' => 2,
        ], 'apcontinue');
        do {
            $res = $queryer->query();
            foreach ($res['query']['allpages'] as $page) {
                if (preg_match($pattern, $page['title'])) {
                    $list[] = $page['title'];
                }
            }
        } while ($queryer->canContinue());
        return $list;
    }
}