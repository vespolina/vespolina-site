<?php

namespace Vespolina\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncGithubCommand extends ContainerAwareCommand
{

    protected $input;
    protected $country;
    protected $output;
    protected $type;
		protected $client;

    protected function configure()
    {
        $this
            ->setName('vespolina:sync-github')
            ->setDescription('Sync github data from vespolina respositories');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$this->client = new \Github\Client();
		$result = $this->syncRepositories($input, $output);

        $output->writeln(count($result['contributors']) . ' unique contributors so far');
        $this->writeContributorsToTwigTemplate($result['contributors']);
    }

	protected function syncRepositories($input, $output)
	{
		
		$contributors = array();
		$repositories = $this->getGitRepositories();

        $totalContributions = 0;

        $rawData = array();
		
		foreach ($repositories as $repository) {
			
			$name = $repository['name'];
			$repoContributors = $this->client->api('repo')->contributors('vespolina', $name);
            $rawData[$name] = $repoContributors;

         	foreach ($repoContributors as $contributor) {
				if (!array_key_exists($contributor['login'], $contributors )) {
					$contributors[$contributor['login']] = $contributor;
			    }

                $totalContributions += $contributor['contributions'];

                if (!isset($contributors[$contributor['login']]['total_contributions'])) {
                    $contributors[$contributor['login']]['total_contributions'] = $contributor['contributions'];
                } else {
                    $contributors[$contributor['login']]['total_contributions'] += $contributor['contributions'];
                }
			}
			$output->writeln('-' . $name . ' with ' . count($repoContributors) . ' contributors');
		}

        file_put_contents('/tmp/githubsync-debug.json', json_encode($rawData, JSON_PRETTY_PRINT));

        return array('contributors' => $contributors, 'repositories' => $repositories);
	}

	protected function getGitRepositories()
	{

		$retrievedRepositories =  $this->client->api('user')->repositories('vespolina');
		$repositories = array();

		foreach ($retrievedRepositories as $repo) {
			if (!$this->isIgnoredRepository($repo['name'])) {
	            $repositories[] = $repo;
			}
		}
	
		return $repositories;
	}

    protected function isIgnoredRepository($name)
    {
        return
            $name == 'molino' ||
            $name == 'dummy';
    }

    protected function writeContributorsToTwigTemplate(array $contributors) {

        $html = '';
        $twigFile = __DIR__ . '/../Resources/views/Default/_contributors.html.twig';


        $unsortedHtmlResult = array();
        foreach ($contributors as $contributor) {

            if (array_key_exists('name', $contributor)) {
                $name = $contributor['name'];
            } else {
                $name = $contributor['login'];
            }

            $gravatarImageUrl = 'http://www.gravatar.com/avatar/' . $contributor['gravatar_id'] ;
            $gitUrl = 'http://www.github.com/' . $contributor['login'];
            $unsortedHtmlResult[$name] = array(
                'html' => '<span class="contributor" data-contributions="' . $contributor['total_contributions'] . '"><a href="' . $gitUrl . '"><img src="'. $gravatarImageUrl . '" alt="' . $name . '"/></a><br />' . $name . '</span>' . PHP_EOL,
                'contributions' => $contributor['total_contributions'],
            );

        }

        usort($unsortedHtmlResult, function ($x, $y) {
            return $x['contributions'] < $y['contributions'] ? 1 : -1;
        });

        $html = '';
        foreach ($unsortedHtmlResult as $htmlResult) {
            $html .= $htmlResult['html'];
        }

        file_put_contents($twigFile, $html);
    }
}
