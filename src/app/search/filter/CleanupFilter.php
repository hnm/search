<?php
namespace search\filter;

use n2n\core\container\TransactionManager;
use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\web\http\ResponseListener;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\payload\Payload;
use search\model\Indexer;
use search\model\dao\SearchEntryDao;

/**
 * This filter makes sure that SearchEntries get removed if the url doesn't exist anymore.
 * @package search\filter
 */
class CleanupFilter extends ControllerAdapter {
	/**
	 * This method registeres {@see CleanupListener}.
	 * @param Indexer $indexer
	 * @param Request $request
	 * @param SearchEntryDao $searchEntryDao
	 * @param TransactionManager $tm
	 */
	public function index(Indexer $indexer, Request $request, SearchEntryDao $searchEntryDao, TransactionManager $tm) {
		$cl = new CleanupListener($indexer, $request, $tm, $searchEntryDao);

		$this->getResponse()->registerListener($cl);
	}
}

/**
 * Makes sure invalid {@see search\bo\SearchEntry SearchEntries} are removed.
 * @package search\filter
 */
class CleanupListener implements ResponseListener {
	private $indexer;
	private $request;
	private $searchEntryDao;
	private $tm;

	public function __construct(Indexer $indexer, Request $request, TransactionManager $transactionManager, SearchEntryDao $searchEntryDao) {
		$this->indexer = $indexer;
		$this->request = $request;
		$this->tm = $transactionManager;
		$this->searchEntryDao = $searchEntryDao;
	}

	/**
	 * When a 404 gets triggered, this method will delete a {@see search\bo\SearchEntry} with the same url, if there is one.
	 * @param Response $response
	 */
	public function onFlush(Response $response) {
		if ($response->getStatus() !== Response::STATUS_404_NOT_FOUND) return;

		$tx = $this->tm->createTransaction();
		$searchEntry = $this->searchEntryDao->getSearchEntryByUrl($this->request->getUrl(), $this->request->getN2nLocale());

		if ($searchEntry === null) return;

		$this->indexer->removeEntry($searchEntry);
		$tx->commit();
	}

	public function onStatusChange(int $newStatus, Response $response) {}

	public function onSend(Payload $payload, Response $response) {}

	public function onReset(Response $response) {}
}