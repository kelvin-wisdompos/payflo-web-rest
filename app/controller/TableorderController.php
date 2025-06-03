<?php


use \Phalcon\Validation;
use \Phalcon\Validation\Exception as VException;
use \Phalcon\Validation\Validator\Email as VEmail;
use \Phalcon\Validation\Validator\InclusionIn as VInclusionIn;
use \Phalcon\Validation\Validator\Regex as VRegex;
use \Phalcon\Validation\Validator\Between as VBetween;
use \Phalcon\Validation\Validator\StringLength as VStringLength;
use \Wisdom\Tool;

class TableorderController extends \Wisdom\ControllerBase
{
    public $salt = 'Wisdompos';
    public $service = null;

    public $TableorderSession;
    public $uniqueId;
    public $tableHash;
    public $table;
    public $ticketId = null;
    public $Ticket = null;
    public $branchSlug = null;
    public $Branch = null;

    public function parseBranch($branchSlug)
    {
        if ($this->branchSlug != null && $this->Branch != null){
            return;
        }
        $Branch = Branch::findFirst([
            'conditions' => 'slug = {slug} AND active = 1',
            'bind' => [
                'slug' => $branchSlug,
            ],
        ]);
        if (!$Branch) {
            throw new Exception('Not found', 404);
        }
        $Company = $Branch->getCompany([
            'conditions' => 'active = 1',
        ]);
        if (!$Company) {
            throw new Exception('Not found', 404);
        }

        $this->branchSlug   = $branchSlug;
        $this->Branch       = $Branch;
    }

    // protected function getService()
    // {
    //     if ($this->service) {
    //         return $this->service;
    //     }
    //     $Service = $this->Branch->getService([
    //         'conditions' => 'tableorder = 1',
    //     ]);
    //     if (!$Service || !isset($Service[0])) {
    //         throw new Exception("Not found", 404);
    //     }
    //     $service = Service::prepareArray($Service[0]);
    //     $this->service = $service;
    //     return $service;
    // }

    // protected function getOpeningHours()
    // {
    //     $openingHour_ = [];
    //     $OpeningHour_ = OpeningHour::find([
    //         'conditions' => 'fk_branch = {fk_branch}',
    //         'bind' => [
    //             'fk_branch' => $this->Branch->id,
    //         ],
    //     ]);
    //     if (!$OpeningHour_) {
    //         return false;
    //     }
    //     foreach ($OpeningHour_ as $key => $OpeningHour) {
    //         $openingHour_[$OpeningHour->weekday] = OpeningHour::prepareArray($OpeningHour);
    //     }

    //     return $openingHour_;
    // }

    // protected function isOrderTimeValid()
    // {
    //     $openingHour_ = $this->getOpeningHours();
    //     $todayWeekday = $this->tool->adjustDate($this->config->timezone, $this->Branch->timezone, 'now', 'N')-1;
    //     $openingHour = $openingHour_[$todayWeekday] ?? null;
    //     if (!$openingHour) {
    //         return false;
    //     }
    //     $rightNow = $this->tool->adjustDate($this->config->timezone, $this->Branch->timezone, 'now', 'H:i:s');

    //     if ($openingHour['open']>$rightNow
    //         || $rightNow>$openingHour['close']
    //         || (!empty($openingHour['breakstart']) && !empty($openingHour['breakend'])
    //             && $openingHour['breakstart']<$rightNow
    //             && $rightNow<$openingHour['breakend']
    //         )) {

    //         $this->flashSession->warning('Table Order operational hour only available from '.date('H:i', strtotime($openingHour['open'])).' to '
    //         .(!empty($openingHour['breakstart']) && !empty($openingHour['breakend']) ? date('H:i', strtotime($openingHour['breakstart'])).' and open again from '.date('H:i', strtotime($openingHour['breakend'])).' to ' : '')
    //         .date('H:i', strtotime($openingHour['close'])).'.');
    //         return false;
    //     }
    //     return true;
    // }

    // public function initialize()
    // {
    //     parent::initialize();

    //     $actionName = $this->dispatcher->getActionName();
    //     $except_ = [];

    //     if (in_array($actionName, $except_)) {
    //         return;
    //     }

    //     if ($this->Branch===null) {
    //         throw new Exception('Not found', 404);
    //     }

    //     if (!$this->tableOrderAvailable) {
    //         throw new Exception("Not found", 404);
    //     }

    //     $this->getService();

    //     if (!$this->isOrderTimeValid()) {
    //         throw new Exception("Not found", 404);
    //     }
    //     $this->view->setLayout('tableorder');
    // }

    protected function parseTable($tableHash)
    {
        if (!$tableHash) {
            return false;
        }
        $Hashids = new \Hashids\Hashids($this->salt, 10);
        $tableId = $Hashids->decode($tableHash)[0]??null;
        if (!$tableId) {
            return false;
        }
        $Table = Table::findFirst([
            'conditions' => 'id = {tableId} AND fk_branch = {branchId} AND active = 1',
            'bind' => [
                'tableId' => $tableId,
                'branchId' => $this->Branch->id,
            ],
        ]);
        if (!$Table) {
            return false;
        }
        $table = Table::prepareArray($Table);
        return $table;
    }

    // protected function parseCategory($categoryId)
    // {
    //     $service = $this->getService();

    //     $Category = Category::findFirst([
    //         'conditions' => 'id = {categoryId} AND fk_branch = {branchId} AND active = 1',
    //         'bind' => [
    //             'categoryId' => $categoryId,
    //             'branchId' => $this->Branch->id,
    //         ],
    //     ]);
    //     if (!$Category) {
    //         return null;
    //     }
    //     if (!$Category->tableorder) {
    //         return null;
    //     }
    //     return Category::prepareArray($Category);
    // }

    // protected function getTicket($fk_ticket, $fk_branch)
    // {
    //     $Ticket = Ticket::findFirst([
    //         'conditions' => 'fk_branch = {fk_branch} AND id = {id} AND fk_void IS NULL',
    //         'bind' => [
    //             'fk_branch' => $fk_branch,
    //             'id' => $fk_ticket,
    //         ],
    //     ]);
    //     if (!$Ticket) {
    //         return false;
    //     }
    //     return $Ticket;
    // }

    // protected function getTableorderSession($uniqueId, $fk_branch)
    // {
    //     $TableorderSession = TableorderSession::findFirst([
    //         'conditions' => 'unique_id = {unique_id} AND fk_branch = {fk_branch}',
    //         'bind' => [
    //             'unique_id' => $uniqueId,
    //             'fk_branch' => $fk_branch,
    //         ],
    //     ]);
    //     if (!$TableorderSession) {
    //         return false;
    //     }
    //     return $TableorderSession;
    // }

    // protected function minutesDiff($time1, $time2)
    // {
    //     $DT1 = new DateTime($time1);
    //     $DT2 = new DateTime($time2);
    //     $DI = $DT1->diff($DT2);
    //     $day = intval($DI->days);
    //     $hour = intval($DI->h);
    //     $min = intval($DI->i);
    //     return
    //         ($day*24*60)+
    //         ($hour*60)+
    //         $min;
    // }

    // protected function legalChecker($uniqueId, $tableHash = null)
    // {
    //     $TableorderSession = $this->getTableorderSession($uniqueId, $this->Branch->id);
    //     if (!$TableorderSession) {
    //         if ($tableHash) {
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/t/'.$tableHash), true);
    //         } else {
    //             throw new Exception("Not found", 404);
    //         }
    //     }

    //     $this->TableorderSession = $TableorderSession;
    //     $this->uniqueId = $uniqueId;

    //     $Table = $TableorderSession->getTable();
    //     $table = Table::prepareArray($Table);
    //     $this->table = $table;

    //     $Hashids = new \Hashids\Hashids($this->salt, 10);
    //     $tableHash = $Hashids->encode($table['id']);
    //     $this->tableHash = $tableHash;

    //     if ($TableorderSession->fk_ticket) {
    //         $Ticket = $TableorderSession->getTicket();
    //         $service = $this->getService();

    //         if ($Ticket->fk_service!==$service['id']) {
    //             throw new Exception('Not found', 404);
    //         }

    //         if ($Ticket->paid) {
    //             if (intval($this->cfg['TABLE_ORDER_DISABLE_ORDER_AFTER_PAID']??0)) {
    //                 if ($this->dispatcher->getActionName()!=='thankyou') {
    //                     // return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/thankyou/'.$TableorderSession->unique_id), true);
    //                     return $this->response->redirect($this->url->get($this->branchSlug.'/t/'.$tableHash), true);
    //                 }
    //             }
    //         }

    //         if ($Ticket->fk_void!=null) {
    //             if ($this->dispatcher->getActionName()!=='thankyou') {
    //                 $this->flashSession->notice('We are sorry, this transaction has been voided. Please re-enter your order again.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/t/'.$tableHash), true);
    //             }
    //         }

    //         if ($Ticket->status==='CLOSED') {
    //             if ($this->dispatcher->getActionName()!=='thankyou') {
    //                 // $this->flashSession->notice('This transaction has been closed. Thank you for your latest order.');
    //                 // return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/thankyou/'.$TableorderSession->unique_id), true);
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/t/'.$tableHash), true);
    //             }
    //         }
    //         $this->ticketId = $TableorderSession->fk_ticket;
    //         $this->Ticket = $Ticket;
    //     }
    // }

    public function index_v1($branchSlug, $tableHash)
    {
        // NEWLY SCAN TABLE QR
        $this->parseBranch($branchSlug);
        $table = $this->parseTable($tableHash);
        if (!$table) {
            throw new Exception("Not found", 404);
        }
        return;

        $DateTimeNow = new DateTime('now', new DateTimeZone($this->Branch->timezone));
        $DateTime00 = new DateTime($DateTimeNow->format('Y-m-d 00:00:00'), new DateTimeZone($this->Branch->timezone));
        $DateTimeNow->setTimezone(new DateTimeZone($this->config->database->timezone));
        $DateTime00->setTimezone(new DateTimeZone($this->config->database->timezone));
        $TableorderSession = TableorderSession::findFirst([
            'conditions' => 'fk_branch = {fk_branch} AND fk_table = {fk_table} AND (created BETWEEN {bfrom} AND {bto})',
            'bind' => [
                'fk_branch' => $this->Branch->id,
                'fk_table' => $table['id'],
                'bfrom' => $DateTime00->format('Y-m-d H:i:s'),
                'bto' => $DateTimeNow->format('Y-m-d H:i:s'),
            ],
            'order' => 'created DESC',
        ]);
        if (!$TableorderSession) {
            // jika tidak ada session buat session baru
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/new/'.$tableHash), true);
        }
        // jika ada session
        if (!$TableorderSession->fk_ticket) {
            // jika ticket belum terbuat, lanjutkan sesi
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$TableorderSession->unique_id), true);
        }
        // jika ticket sudah terbuat, check close tidaknya ticket
        $Ticket = Ticket::findFirst($TableorderSession->fk_ticket);
        if (!$Ticket) {
            // jika ticket tidak ditemukan (ini kejadian yg seharusnya tidak terjadi), penanganan sementara buat sesi baru
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/new/'.$tableHash), true);
        }

        if (!empty($TableorderSession->request_bill)) {
            // jika sesi sudah request bill
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/new/'.$tableHash), true);
        }

        if ($Ticket->paid) {
            // jika ticket sudah terbayar lunas
            if (intval($this->cfg['TABLE_ORDER_DISABLE_ORDER_AFTER_PAID']??0)) {
                // return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/thankyou/'.$TableorderSession->unique_id), true);
                return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/new/'.$tableHash), true);
            }
        }

        if ($Ticket->status==='CLOSED' || $Ticket->fk_void!=null) {
            // jika ticket sudah di close maupun ticket sudah di void maka buat sesi baru
            // return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/thankyou/'.$TableorderSession->unique_id), true);
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/new/'.$tableHash), true);
        }

        // jika ticket masih aktif, lanjutkan sesi
        if (($this->cfg['TABLE_ORDER_SESSION_TIMEOUT']??0)>0) {
            // lebih dari 30 menit window di tanyakan apa ini sesinya
            $interval = $this->minutesDiff($TableorderSession->created, 'now');
            $timeout = intval($this->cfg['TABLE_ORDER_SESSION_TIMEOUT']);
            if ($interval>$timeout) {
                //jika timeout ask for continue
                return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/continue/'.$TableorderSession->unique_id), true);
            } else {
                return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$TableorderSession->unique_id), true);
            }
        } else {
            return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$TableorderSession->unique_id), true);
        }
    }

    // public function newAction($tableHash)
    // {
    //     $table = $this->parseTable($tableHash);
    //     if (!$table) {
    //         throw new Exception("Not found", 404);
    //     }
    //     // kosongkan cart
    //     $this->session->set('tableorder-cart_', []);
    //     // buat unik id
    //     $random = new \Phalcon\Security\Random();
    //     $uniqueId = $random->uuid();
    //     $TableorderSession = new TableorderSession();
    //     $TableorderSession->unique_id = $uniqueId;
    //     $TableorderSession->fk_branch = $this->Branch->id;
    //     $TableorderSession->fk_table = $table['id'];
    //     $TableorderSession->fk_ticket = null;
    //     $TableorderSession->created = date('Y-m-d H:i:s');
    //     if (!$TableorderSession->save()) {
    //         throw new Exception(implode(', ', $TableorderSession->getMessages()), 500);
    //     }

    //     // redirect ke purchase untuk memulai sesi
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    // }

    // public function purchaseAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();

    //     $getQuery = [];
    //     $Category_ = Category::find([
    //         'conditions' => 'fk_branch = {branchId} AND active = 1 AND fk_category IS NULL',
    //         'bind' => [
    //             'branchId' => $this->Branch->id,
    //         ],
    //         'order' => 'ordinal ASC, name ASC',
    //     ]);
    //     $category_ = ['ordinal'=>[]];
    //     foreach ($Category_ as $key => $Category) {
    //         if (!$Category->tableorder) {
    //             continue;
    //         }
    //         $count = Menu::categoryCount($this->Branch->id, $service['id'], $Category->id);
    //         if (intval($count)===0) {
    //             continue;
    //         }
    //         $category = Category::prepareArray($Category);
    //         $category['category_'] = $this->getCategoryChild($category['id'], 0);
    //         $category_[$category['id']] = $category;
    //         $category_['ordinal'][] = $category['id'];
    //     }

    //     $categoryId = $this->request->getQuery('c', 'int', null);
    //     if (!$categoryId) {
    //         $category = null;
    //         foreach ($category_['ordinal'] as $key => $categoryId2) {
    //             $category2 = $category_[$categoryId2];
    //             if ($category2['tableorder_favorite']==1) {
    //                 $category = $category2;
    //                 break;
    //             }
    //         }
    //         if (!$category) {
    //             $category = $category_[$category_['ordinal'][0]]??null;
    //         }
    //     } else {
    //         $category = $this->parseCategory($categoryId);
    //     }
    //     if ($category) {
    //         $getQuery['c'] = $category['id'];
    //     }

    //     $menu_ = ['ordinal'=>[]];
    //     $query = $this->request->getQuery('q');
    //     if (!empty($query)) {
    //         $getQuery['q'] = $query;
    //         $menu_ = Menu::searchResult($this->Branch->id, $service['id'], $query);
    //     } else if($category) {
    //         $menu_ = Menu::categoryResult($this->Branch->id, $service['id'], $category['id']);
    //     }

    //     $menu_ = Table::filterMenu($this->table['id'], $menu_);

    //     foreach ($menu_['ordinal'] as $key1 => $menuId) {
    //         $menu = &$menu_[$menuId];
    //         if ($menu['active']==0) {
    //             continue;
    //         }
    //         $menu['hasChoice'] = Menu::hasChoice($menuId);
    //         $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $menuId);
    //         if (count($discount_['ordinal'])===0) {
    //             continue;
    //         }
    //         foreach ($discount_['ordinal'] as $key2 => $discountId) {
    //             $discount = $discount_[$discountId];
    //             if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                 $this->applyDiscount($menu, $discount);
    //             }
    //         }
    //     }

    //     $ticketId = $this->ticketId;
    //     $ticket = null;
    //     if ($ticketId) {
    //         $ticket = $this->getTicketArray($ticketId);
    //         if (!$ticket) {
    //             $ticket = null;
    //         }
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'table' => $table,
    //         'tableHash' => $tableHash,
    //         'service' => $service,
    //         'getQuery' => $getQuery,
    //         'category' => $category,
    //         'query' => $query,
    //         'category_' => $category_,
    //         'menu_' => $menu_,
    //         'ticket' => $ticket,
    //     ]);
    // }

    // protected function getCategoryChild($categoryId, $level = 0)
    // {
    //     $service = $this->getService();
    //     $serviceId = $service['id'];
    //     if ($level>1) {
    //         return ['ordinal' => []];
    //     }
    //     $Category_ = Category::find([
    //         'conditions' => 'fk_branch = {branchId} AND active = 1 AND fk_category = {parentId} AND tableorder = 1',
    //         'bind' => [
    //             'branchId' => $this->Branch->id,
    //             'parentId' => $categoryId,
    //         ],
    //         'order' => 'ordinal ASC, name ASC',
    //     ]);
    //     if(!$Category_ || count((array) $Category_)===0) {
    //         return ['ordinal' => []];
    //     }
    //     $category_ = ['ordinal'=>[]];
    //     foreach ($Category_ as $key => $Category) {
    //         $count = Menu::categoryCount($this->Branch->id, $serviceId, $Category->id);
    //         if (intval($count)===0) {
    //             continue;
    //         }
    //         $category = Category::prepareArray($Category);
    //         $category['category_'] = $this->getCategoryChild($category['id'], 0);
    //         $category_[$category['id']] = $category;
    //         $category_['ordinal'][] = $category['id'];
    //     }
    //     return $category_;
    // }

    // protected function applyDiscount(&$menu, $discount)
    // {
    //     if (!isset($menu['new_price'])) {
    //         $menu['new_price'] = $menu['price'];
    //     }
    //     if ($discount['type']==='PERCENT') {
    //         if ($discount['cap']===null) {
    //             $menu['new_price'] = max($menu['new_price']-($menu['price']*$discount['percent']), 0);
    //         } else {
    //             $menu['new_price'] = max($menu['new_price']-min($menu['price']*$discount['percent'], $discount['cap']), 0);
    //         }
    //     } else if($discount['type']==='AMOUNT') {
    //         $menu['new_price'] = max($menu['new_price'] - $discount['amount'], 0);
    //     } else if($discount['type']==='FORCE_AMOUNT') {
    //         $menu['new_price'] = $discount['amount'];
    //     }
    //     $menu['new_price'] = number_format($menu['new_price'], 2, '.', '');
    // }

    // protected function ruleValidate($rule_)
    // {
    //     $DateTimeZone1 = new \DateTimeZone($this->config->database->timezone);
    //     $DateTime      = new \DateTime('now', $DateTimeZone1);
    //     $DateTimeZone2 = new \DateTimeZone($this->config->timezone);
    //     $DateTime->setTimezone($DateTimeZone2);
    //     $weekday  = $DateTime->format('w');
    //     $todayDate = $DateTime->format('Y-m-d');
    //     $todayTime = $DateTime->format('H:i:s');
    //     $customer = $this->session->get('customer', null);

    //     foreach ($rule_['ordinal'] as $key => $ruleId) {
    //         $rule = $rule_[$ruleId];
    //         if ($rule['ticket_qty']!==null || $rule['ticket_amount']!==null || $rule['ticket_item_qty']!==null || $rule['ticket_item_amount']!==null) {
    //             // jika terdapat rule yang mengenai ticket maka di-false kan dulu karena akan di-cek di halaman checkout
    //             continue;
    //         }
    //         if ($rule['customer']==1 && !$customer) {
    //             continue;
    //         }
    //         if ($rule['customer']==2 && $customer['type']!=='LOYALTY') {
    //             continue;
    //         }
    //         if ($rule['weekday']!==null && intval($rule['weekday'])!==intval($weekday)) {
    //             continue;
    //         }
    //         if ($rule['from_date']!==null && $rule['from_date']>$todayDate) {
    //             continue;
    //         }
    //         if ($rule['to_date']!==null && $rule['to_date']<$todayDate) {
    //             continue;
    //         }
    //         if ($rule['from_time']!==null && $rule['from_time']>$todayTime) {
    //             continue;
    //         }
    //         if ($rule['to_time']!==null && $rule['to_time']<$todayTime) {
    //             continue;
    //         }
    //         return true;
    //     }

    //     return false;
    // }

    // public function menuAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $serviceCategoryIdentifier = 'tableorder';

    //     $getQuery = [];
    //     $categoryId = $this->request->getQuery('c', 'int', null);
    //     $category = null;
    //     if ($categoryId) {
    //         $category = $this->parseCategory($categoryId);
    //         if ($category) {
    //             $getQuery['c'] = $category['id'];
    //         }
    //     }

    //     $query = $this->request->getQuery('q');
    //     if ($query) {
    //         $getQuery['q'] = $query;
    //     }

    //     $menuId = $this->request->getQuery('id', 'int', null);
    //     $menu = Menu::get($this->Branch->id, $service['id'], $menuId);
    //     if (!$menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if ($menu['package']) {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/package/'.$uniqueId, $getQuery+[
    //             'id'=>$menuId,
    //         ]), true);
    //     }
    //     $menu['qty'] = 1;

    //     $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $menuId);
    //     if (count($discount_['ordinal'])>0) {
    //         foreach ($discount_['ordinal'] as $key => $discountId) {
    //             $discount = $discount_[$discountId];
    //             if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                 $this->applyDiscount($menu, $discount);
    //             }
    //         }
    //     }
    //     $menu['discount_'] = $discount_;

    //     $Menu = Menu::findFirst($menu['id']);
    //     if (!$Menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     $ChoiceGroup_ = $Menu->getChoiceGroup([
    //         'conditions' => 'active = 1',
    //         'order' => 'MMMenuChoiceGroup.ordinal ASC'
    //     ]);
    //     $choiceGroup_ = ChoiceGroup::prepareArray($ChoiceGroup_);
    //     if ($ChoiceGroup_) {
    //         foreach ($ChoiceGroup_ as $key => $ChoiceGroup) {
    //             $choice_ = Choice::choiceGroupResult($this->Branch->id, $service['id'], $ChoiceGroup->id);
    //             if (count($choice_['ordinal'])===0) {
    //                 array_splice($choiceGroup_['ordinal'], array_search($ChoiceGroup->id, $choiceGroup_['ordinal']), 1);
    //                 unset($choiceGroup_[$ChoiceGroup->id]);
    //                 continue;
    //             }
    //             $choiceGroup_[$ChoiceGroup->id]['choice_'] = $choice_;
    //             if ($ChoiceGroup->min!==null && $ChoiceGroup->min>0) {
    //                 $viewpage = true;
    //             }
    //         }
    //     }

    //     $imageLocation = 'img/menu/'.$this->Branch->fk_company.'/'.$this->Branch->id;
    //     $headerImage = null;
    //     if ($this->cfg['TABLE_ORDER_MENU_USING_IMAGE']) {
    //         if (isset($menu['image_url']) && !empty($menu['image_url'])) {
    //             $headerImage = '/'.$imageLocation.'/'.$menu['image_url'];
    //         } else {
    //             $headerImage = '/img/menu-no-image.jpg';
    //         }
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'showQtyButton' => true,
    //         'headerImage' => $headerImage,
    //         'imageLocation' => $imageLocation,
    //         'serviceCategoryIdentifier' => $serviceCategoryIdentifier,
    //         'service' => $service,
    //         'getQuery' => $getQuery,
    //         'menu' => $menu,
    //         'discount_' => $discount_,
    //         'choiceGroup_' => $choiceGroup_,
    //     ]);
    // }

    // public function addAction($uniqueId)
    // {
    //     if (!$this->request->isPost()) {
    //         throw new Exception("Bad request", 400);
    //     }
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();

    //     $getQuery = [];
    //     $categoryId = $this->request->getQuery('c', 'int');
    //     $category = null;
    //     if ($categoryId) {
    //         $category = $this->parseCategory($categoryId);
    //         if ($category) {
    //             $getQuery['c'] = $category['id'];
    //         }
    //     }

    //     $query = $this->request->getQuery('q');
    //     if ($query) {
    //         $getQuery['q'] = $query;
    //     }

    //     $menuId = $this->request->getPost('id', 'int');
    //     $qty = $this->request->getPost('qty', 'int');
    //     $note = $this->request->getPost('note', 'string', null);

    //     $menu = Menu::get($this->Branch->id, $service['id'], $menuId);
    //     if (!$menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if($menu['sold_out']??0) {
    //         $this->flashSession->notice('Sorry, '.$menu['name'].' is sold out.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId, $getQuery), true);
    //     }
    //     $menu['qty'] = $qty;
    //     $menu['note'] = $note;
    //     $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $menuId);
    //     $menu['discount_'] = $discount_;

    //     $choice_ = [];
    //     $chosen_ = $this->request->getPost('choice')??[];
    //     foreach ($chosen_ as $choiceGroupId => $chosen) {
    //         $ChoiceGroup = ChoiceGroup::findFirst([
    //             'conditions' => 'id = {choiceGroupId} AND active = 1',
    //             'bind' => [
    //                 'choiceGroupId' => $choiceGroupId,
    //             ],
    //         ]);
    //         if (!$ChoiceGroup) {
    //             continue;
    //         }
    //         $choiceId_ = [];
    //         foreach ($chosen as $choiceId => $choiceQty) {
    //             $choice = Choice::get($this->Branch->id, $service['id'], $choiceId, $choiceGroupId);
    //             if ($choice) {
    //                 $choiceId_[] = $choiceId;
    //                 $choice_[] = $choice+[
    //                     'fk_choice_group' => $ChoiceGroup->id,
    //                     'choice_group_name' => $ChoiceGroup->name,
    //                     'qty' => $choiceQty,
    //                 ];
    //             }
    //         }
    //         // get choice that default
    //         $Choice_ = $ChoiceGroup->getChoice([
    //             'conditions' => 'MMChoiceGroupChoice.default_qty>0 AND Choice.id NOT IN ({choiceIds:array}) AND active = 1',
    //             'bind' => [
    //                 'choiceIds' => $choiceId_,
    //             ],
    //             'columns' => [
    //                 'Choice.id',
    //                 'Choice.fk_branch',
    //                 'Choice.name',
    //                 'Choice.gst',
    //                 'Choice.stock',
    //                 'Choice.information',
    //                 'Choice.active',
    //                 'MMChoiceGroupChoice.default_qty',
    //             ],
    //         ]);
    //         foreach ($Choice_ as $key => $Choice) {
    //             $choice = Choice::prepareArray($Choice);
    //             if ($choice) {
    //                 $choice_[] = $choice+[
    //                     'fk_choice_group' => $ChoiceGroup->id,
    //                     'choice_group_name' => $ChoiceGroup->name,
    //                     'qty' => 0,
    //                 ];
    //             }
    //         }
    //     }

    //     $this->view->disable();
    //     $this->addToCart($menu, $choice_);
    //     $this->flashSession->success($menu['name'].' has been added to your order.');
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId, $getQuery), true);
    // }

    // protected function addToCart($menu, $choice_ = [])
    // {
    //     $cart_ = $this->session->get('tableorder-cart_', []);

    //     $gstValue = $this->cfg['GST'];
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $gstValue = 0;
    //     }

    //     $cartItem = [
    //         'menuId' => intval($menu['id']),
    //         'menuName' => $menu['name'],
    //         'menuSpicy' => $menu['spicy'],
    //         'menuChef' => $menu['chef'],
    //         'menuPopular' => $menu['popular'],
    //         'menuGst' => floatval($menu['gst'] ? $gstValue : 0),
    //         'menuPrice' => floatval($menu['price']),
    //         'menuPackage' => intval($menu['package']),
    //         'menuImageUrl' => $menu['image_url'],
    //         'menuThumbnailUrl' => $menu['image_thumbnail_url'],
    //         'qty' => intval($menu['qty']??1),
    //         'note' => $menu['note']??null,
    //         'discount_' => $menu['discount_']??[],
    //     ];

    //     $choiceTotalPerItem = 0;
    //     $choiceGstPerItem = 0;

    //     $choiceItem_ = [];
    //     foreach ($choice_ as $key => $choice) {
    //         $choiceItem = [
    //             'choiceGroupId' => intval($choice['fk_choice_group']),
    //             'choiceGroupName' => $choice['choice_group_name'],
    //             'choiceId' => intval($choice['id']),
    //             'choiceName' => $choice['name'],
    //             'choiceGst' => floatval($choice['gst'] ? $gstValue : 0),
    //             'choicePrice' => floatval($choice['price']),
    //             'choiceDefaultQty' => intval($choice['default_qty'])?:null,
    //             'qty' => intval($choice['qty']??1),
    //         ];
    //         $choiceItem['total'] = $choiceItem['qty']*$choiceItem['choicePrice'];
    //         $choiceItem['gst'] = round(($choiceItem['choiceGst']/(1+$choiceItem['choiceGst']))*$choiceItem['total'], 2);
    //         $choiceItem_[] = $choiceItem;

    //         $choiceTotalPerItem += $choiceItem['total'];
    //         $choiceGstPerItem += $choiceItem['gst'];
    //     }
    //     $cartItem['choiceItem_'] = $choiceItem_;

    //     $subtotalPerItem = $cartItem['menuPrice']+$choiceTotalPerItem;
    //     $subtotal = $cartItem['qty']*$subtotalPerItem;
    //     $grandTotalPerItem = $subtotalPerItem;

    //     if (isset($menu['discount_'], $menu['discount_']['ordinal']) && count($menu['discount_']['ordinal'])>0) {
    //         $discount_ = $menu['discount_'];
    //         foreach ($discount_['ordinal'] as $key => $discountId) {
    //             $discount = $discount_[$discountId];
    //             if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                 if ($discount['type']==='PERCENT') {
    //                     if ($discount['cap']===null) {
    //                         $grandTotalPerItem = max($grandTotalPerItem-($subtotalPerItem*$discount['percent']), 0);
    //                     } else {
    //                         $grandTotalPerItem = max($grandTotalPerItem-min($subtotalPerItem*$discount['percent'], $discount['cap']), 0);
    //                     }
    //                 } elseif ($discount['type']==='AMOUNT') {
    //                     $grandTotalPerItem = max($grandTotalPerItem - $discount['amount'], 0);
    //                 } elseif ($discount['type']==='FORCE_AMOUNT') {
    //                     $grandTotalPerItem = $discount['amount'];
    //                 }
    //             }
    //         }
    //     }
    //     $grandTotal = $cartItem['qty']*$grandTotalPerItem;

    //     $grandTotal = floatval($grandTotal);
    //     $discountTotal = $subtotal-$grandTotal;
    //     $itemGst = (($cartItem['menuGst']/(1+$cartItem['menuGst'])*$cartItem['menuPrice'])+$choiceGstPerItem)*$cartItem['qty'];
    //     $gst = round($grandTotal/$subtotal*$itemGst, 2);

    //     $cartItem['choiceTotal'] = $choiceTotalPerItem;
    //     $cartItem['choiceGst'] = $choiceGstPerItem;
    //     $cartItem['subtotal'] = $subtotal;
    //     $cartItem['grandTotal'] = $grandTotal;
    //     $cartItem['discountTotal'] = $discountTotal;
    //     $cartItem['gst'] = $gst;

    //     $cart_[] = $cartItem;
    //     $this->session->set('tableorder-cart_', $cart_);
    // }

    // public function packageAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();

    //     $getQuery = [];
    //     $categoryId = $this->request->getQuery('c', 'int');
    //     $category = null;
    //     if ($categoryId) {
    //         $category = $this->parseCategory($categoryId);
    //         if ($category) {
    //             $getQuery['c'] = $category['id'];
    //         }
    //     }

    //     $query = $this->request->getQuery('q');
    //     if ($query) {
    //         $getQuery['q'] = $query;
    //     }

    //     $menuId = $this->request->getQuery('id', 'int');
    //     $menu = Menu::get($this->Branch->id, $service['id'], $menuId);
    //     if (!$menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if (!$menu['package']) {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/menu/'.$uniqueId, $getQuery+[
    //             'id'=>$menuId,
    //         ]), true);
    //     }
    //     $Menu = Menu::findFirst($menuId);
    //     if(!$Menu) {
    //         throw new Exception("Not found", 404);
    //     }

    //     $Package_ = $Menu->getPackage([
    //         'order' => 'ordinal ASC',
    //     ]);
    //     $package_ = Package::prepareArray($Package_);
    //     foreach ($Package_ as $key1 => $Package) {
    //         $package = &$package_[$Package->id];
    //         $PackageOption_ = $Package->getPackageOption();
    //         $packageOption_ = PackageOption::prepareArray($PackageOption_);
    //         foreach ($PackageOption_ as $key2 => $PackageOption) {
    //             $packageOption = &$packageOption_[$PackageOption->id];
    //             $Menu2 = $PackageOption->getMenu();
    //             $menu2 = Menu::prepareArray($Menu2);
    //             $packageOption += $menu2;
    //         }
    //         $packageOption_ = Table::filterPackageOption($this->table['id'], $packageOption_);
    //         $package['packageOption_'] = $packageOption_;
    //     }

    //     $imageLocation = 'img/menu/'.$this->Branch->fk_company.'/'.$this->Branch->id;
    //     $headerImage = null;
    //     if ($this->cfg['TABLE_ORDER_MENU_USING_IMAGE']) {
    //         if (isset($menu['image_url']) && !empty($menu['image_url'])) {
    //             $headerImage = '/'.$imageLocation.'/'.$menu['image_url'];
    //         } else {
    //             $headerImage = '/img/menu-no-image.jpg';
    //         }
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'headerImage' => $headerImage,
    //         'imageLocation' => $imageLocation,
    //         'service' => $service,
    //         'getQuery' => $getQuery,
    //         'menu' => $menu,
    //         'package_' => $package_,
    //     ]);
    // }

    // public function packageOptionAction($uniqueId)
    // {
    //     if (!$this->request->isPost()) {
    //         throw new Exception("Bad request", 400);
    //     }
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $serviceCategoryIdentifier = 'tableorder';

    //     $getQuery = [];
    //     $categoryId = $this->request->getQuery('c', 'int');
    //     $category = null;
    //     if ($categoryId) {
    //         $category = $this->parseCategory($categoryId);
    //         if ($category) {
    //             $getQuery['c'] = $category['id'];
    //         }
    //     }

    //     $query = $this->request->getQuery('q');
    //     if ($query) {
    //         $getQuery['q'] = $query;
    //     }

    //     $menuId = $this->request->getPost('id', 'int');
    //     $menu = Menu::get($this->Branch->id, $service['id'], $menuId);
    //     if (!$menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if (!$menu['package']) {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/menu/'.$uniqueId, $getQuery+[
    //             'id'=>$menuId,
    //         ]), true);
    //     }
    //     $Menu = Menu::findFirst($menuId);
    //     if(!$Menu) {
    //         throw new Exception("Not found", 404);
    //     }

    //     $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $menuId);
    //     $menu['discount_'] = $discount_;

    //     $selected_ = $this->request->getPost('package-option', null, []);

    //     $Package_ = $Menu->getPackage([
    //         'order' => 'ordinal ASC',
    //     ]);
    //     $package_ = Package::prepareArray($Package_);
    //     foreach ($Package_ as $key1 => $Package) {
    //         $package = &$package_[$Package->id];
    //         if (!isset($selected_[$Package->id])) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         $PackageOption = PackageOption::findFirst([
    //             'conditions' => 'fk_package = {packageId} AND fk_menu = {menuId}',
    //             'bind' => [
    //                 'packageId' => $Package->id,
    //                 'menuId' => $selected_[$Package->id],
    //             ],
    //         ]);
    //         if(!$PackageOption) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         $Menu2 = $PackageOption->getMenu();
    //         if (!$Menu2) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         $menu2 = Menu::prepareArray($Menu2);

    //         $ChoiceGroup_ = $Menu2->getChoiceGroup([
    //             'conditions' => 'active = 1',
    //             'order' => 'MMMenuChoiceGroup.ordinal ASC'
    //         ]);
    //         $choiceGroup_ = ChoiceGroup::prepareArray($ChoiceGroup_);
    //         if ($ChoiceGroup_) {
    //             foreach ($ChoiceGroup_ as $key2 => $ChoiceGroup) {
    //                 $choice_ = Choice::choiceGroupResult($this->Branch->id, $service['id'], $ChoiceGroup->id);
    //                 if (count($choice_['ordinal'])>0) {
    //                     $choiceGroup_[$ChoiceGroup->id]['choice_'] = $choice_;
    //                 } else {
    //                     array_splice($choiceGroup_['ordinal'], array_search($ChoiceGroup->id, $choiceGroup_['ordinal']), 1);
    //                     unset($choiceGroup_[$ChoiceGroup->id]);
    //                 }
    //             }
    //         }
    //         $menu2['choiceGroup_'] = $choiceGroup_;
    //         $package['menu'] = $menu2;
    //     }

    //     $imageLocation = 'img/menu/'.$this->Branch->fk_company.'/'.$this->Branch->id;
    //     $headerImage = null;
    //     if ($this->cfg['TABLE_ORDER_MENU_USING_IMAGE']) {
    //         if (isset($menu['image_url']) && !empty($menu['image_url'])) {
    //             $headerImage = '/'.$imageLocation.'/'.$menu['image_url'];
    //         } else {
    //             $headerImage = '/img/menu-no-image.jpg';
    //         }
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'showQtyButton' => true,
    //         'headerImage' => $headerImage,
    //         'imageLocation' => $imageLocation,
    //         'service' => $service,
    //         'getQuery' => $getQuery,
    //         'menu' => $menu,
    //         'discount_' => $discount_,
    //         'package_' => $package_,
    //         'serviceCategoryIdentifier' => $serviceCategoryIdentifier,
    //     ]);
    // }

    // public function addPackageAction($uniqueId)
    // {
    //     if (!$this->request->isPost()) {
    //         throw new Exception("Bad request", 400);
    //     }
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();

    //     $getQuery = [];
    //     $categoryId = $this->request->getQuery('c', 'int');
    //     $category = null;
    //     if ($categoryId) {
    //         $category = $this->parseCategory($categoryId);
    //         if ($category) {
    //             $getQuery['c'] = $category['id'];
    //         }
    //     }

    //     $query = $this->request->getQuery('q');
    //     if ($query) {
    //         $getQuery['q'] = $query;
    //     }

    //     $menuId = $this->request->getPost('id', 'int');
    //     $qty = $this->request->getPost('qty', 'int');
    //     $note = $this->request->getPost('note', 'string', null);

    //     $menu = Menu::get($this->Branch->id, $service['id'], $menuId);
    //     if (!$menu) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if($menu['sold_out']??0) {
    //         $this->flashSession->notice('Sorry, '.$menu['name'].' is sold out.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId, $getQuery), true);
    //     }
    //     if (!$menu['package']) {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/menu/'.$uniqueId, $getQuery+[
    //             'id'=>$menuId,
    //         ]), true);
    //     }
    //     $menu['qty'] = $qty;
    //     $menu['note'] = $note;
    //     $Menu = Menu::findFirst($menuId);
    //     if(!$Menu) {
    //         throw new Exception("Not found", 404);
    //     }

    //     $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $menuId);
    //     $menu['discount_'] = $discount_;

    //     $selected_ = $this->request->getPost('menu', null, []);
    //     $selectedChoices_ = $this->request->getPost('choice', null, []);

    //     $Package_ = $Menu->getPackage([
    //         'order' => 'ordinal ASC',
    //     ]);
    //     $package_ = Package::prepareArray($Package_);
    //     $menuPackage_ = [];
    //     foreach ($Package_ as $key1 => $Package) {
    //         $package = &$package_[$Package->id];
    //         if (!isset($selected_[$Package->id])) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         $PackageOption = PackageOption::findFirst([
    //             'conditions' => 'fk_package = {packageId} AND fk_menu = {menuId}',
    //             'bind' => [
    //                 'packageId' => $Package->id,
    //                 'menuId' => $selected_[$Package->id],
    //             ],
    //         ]);
    //         if (!$PackageOption) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         $Menu2 = $PackageOption->getMenu();
    //         if (!$Menu2) {
    //             throw new Exception("Bad request", 400);
    //         }
    //         for($i=0; $i<$Package->qty; $i++) {
    //             $menu2 = Menu::prepareArray($Menu2);
    //             $menu2['fk_service'] = $service['id'];
    //             $menu2['qty'] = 1;

    //             $choice_ = [];
    //             $chosen_ = $selectedChoices_[$Package->id][$i]??[];
    //             foreach ($chosen_ as $choiceGroupId => $chosen) {
    //                 $ChoiceGroup = ChoiceGroup::findFirst([
    //                     'conditions' => 'id = {choiceGroupId} AND active = 1',
    //                     'bind' => [
    //                         'choiceGroupId' => $choiceGroupId,
    //                     ],
    //                 ]);
    //                 if (!$ChoiceGroup) {
    //                     continue;
    //                 }
    //                 $choiceId_ = [];
    //                 foreach ($chosen as $choiceId => $choiceQty) {
    //                     $choice = Choice::get($this->Branch->id, $service['id'], $choiceId, $choiceGroupId);
    //                     if ($choice) {
    //                         $choiceId_[] = $choiceId;
    //                         $choice_[] = $choice+[
    //                             'fk_choice_group' => $ChoiceGroup->id,
    //                             'choice_group_name' => $ChoiceGroup->name,
    //                             'qty' => $choiceQty,
    //                         ];
    //                     }
    //                 }
    //                 // get choice that default
    //                 $Choice_ = $ChoiceGroup->getChoice([
    //                     'conditions' => 'MMChoiceGroupChoice.default_qty>0 AND Choice.id NOT IN ({choiceIds:array}) AND active = 1',
    //                     'bind' => [
    //                         'choiceIds' => $choiceId_,
    //                     ],
    //                     'columns' => [
    //                         'Choice.id',
    //                         'Choice.fk_branch',
    //                         'Choice.name',
    //                         'Choice.gst',
    //                         'Choice.stock',
    //                         'Choice.information',
    //                         'Choice.active',
    //                         'MMChoiceGroupChoice.default_qty',
    //                     ],
    //                 ]);
    //                 foreach ($Choice_ as $key => $Choice) {
    //                     $choice = Choice::prepareArray($Choice);
    //                     if ($choice) {
    //                         $choice_[] = $choice+[
    //                             'fk_choice_group' => $ChoiceGroup->id,
    //                             'choice_group_name' => $ChoiceGroup->name,
    //                             'qty' => 0,
    //                         ];
    //                     }
    //                 }
    //             }
    //             $menu2['choice_'] = $choice_;
    //             $menuPackage_[] = $menu2;
    //         }
    //     }

    //     $this->view->disable();
    //     $this->addPackageToCart($menu, $menuPackage_);
    //     $this->flashSession->success($menu['name'].' has been added to your order.');
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId, $getQuery), true);
    // }

    // protected function addPackageToCart($menu, $menuChild_)
    // {
    //     $cart_ = $this->session->get('tableorder-cart_', []);

    //     $gstValue = $this->cfg['GST'];
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $gstValue = 0;
    //     }

    //     $cartItem = [
    //         'menuId' => intval($menu['id']),
    //         'menuName' => $menu['name'],
    //         'menuSpicy' => $menu['spicy'],
    //         'menuChef' => $menu['chef'],
    //         'menuPopular' => $menu['popular'],
    //         'menuGst' => floatval($menu['gst'] ? $gstValue : 0),
    //         'menuPrice' => floatval($menu['price']),
    //         'menuPackage' => 1,
    //         'menuImageUrl' => $menu['image_url'],
    //         'menuThumbnailUrl' => $menu['image_thumbnail_url'],
    //         'qty' => intval($menu['qty']??1),
    //         'note' => $menu['note']??null,
    //         'discount_' => $menu['discount_']??[],
    //     ];

    //     $choiceTotalPerItem = 0;
    //     $choiceGstPerItem = 0;

    //     $child_ = [];
    //     foreach ($menuChild_ as $key1 => $menuChild) {
    //         $childItem = [
    //             'menuId' => intval($menuChild['id']),
    //             'menuName' => $menuChild['name'],
    //             'menuGst' => floatval($menuChild['gst'] ? $gstValue : 0),
    //             'menuPrice' => 0,
    //             'menuPackage' => 0,
    //             'menuImageUrl' => $menu['image_url'],
    //             'menuThumbnailUrl' => $menu['image_thumbnail_url'],
    //             'qty' => intval($menuChild['qty']??1),
    //             'note' => $menuChild['note']??null,
    //         ];
    //         $choiceItem_ = [];
    //         foreach ($menuChild['choice_'] as $key2 => $choice) {
    //             $choiceItem = [
    //                 'choiceGroupId' => intval($choice['fk_choice_group']),
    //                 'choiceGroupName' => $choice['choice_group_name'],
    //                 'choiceId' => intval($choice['id']),
    //                 'choiceName' => $choice['name'],
    //                 'choiceGst' => floatval($choice['gst'] ? $gstValue : 0),
    //                 'choicePrice' => floatval($choice['price']),
    //                 'choiceDefaultQty' => intval($choice['default_qty'])?:null,
    //                 'qty' => intval($choice['qty']??1),
    //             ];
    //             $choiceItem['total'] = $choiceItem['qty']*$choiceItem['choicePrice'];
    //             $choiceItem['gst'] = round(($choiceItem['choiceGst']/(1+$choiceItem['choiceGst']))*$choiceItem['total'], 2);
    //             $choiceItem_[] = $choiceItem;

    //             $choiceTotalPerItem += $choiceItem['total'];
    //             $choiceGstPerItem += $choiceItem['gst'];
    //         }
    //         $childItem['choiceItem_'] = $choiceItem_;
    //         $child_[] = $childItem;
    //     }
    //     $cartItem['child_'] = $child_;

    //     $subtotalPerItem = $cartItem['menuPrice']+$choiceTotalPerItem;
    //     $subtotal = $cartItem['qty']*$subtotalPerItem;
    //     $grandTotalPerItem = $subtotalPerItem;

    //     if (isset($menu['discount_'], $menu['discount_']['ordinal']) && count($menu['discount_']['ordinal'])) {
    //         $discount_ = $menu['discount_'];
    //         foreach ($discount_['ordinal'] as $key => $discountId) {
    //             $discount = $discount_[$discountId];
    //             if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                 if ($discount['type']==='PERCENT') {
    //                     if ($discount['cap']===null) {
    //                         $grandTotalPerItem = max($grandTotalPerItem-($subtotalPerItem*$discount['percent']), 0);
    //                     } else {
    //                         $grandTotalPerItem = max($grandTotalPerItem-min($subtotalPerItem*$discount['percent'], $discount['cap']), 0);
    //                     }
    //                 } else if($discount['type']==='AMOUNT') {
    //                     $grandTotalPerItem = max($grandTotalPerItem - $discount['amount'], 0);
    //                 } else if($discount['type']==='FORCE_AMOUNT') {
    //                     $grandTotalPerItem = $discount['amount'];
    //                 }
    //             }
    //         }
    //     }
    //     $grandTotal = floatval($cartItem['qty']*$grandTotalPerItem);
    //     $discountTotal = $subtotal-$grandTotal;
    //     $itemGst = (($cartItem['menuGst']/(1+$cartItem['menuGst'])*$cartItem['menuPrice'])+$choiceGstPerItem)*$cartItem['qty'];
    //     $gst = round($grandTotal/$subtotal*$itemGst, 2);

    //     $cartItem['choiceTotal'] = $choiceTotalPerItem;
    //     $cartItem['choiceGst'] = $choiceGstPerItem;
    //     $cartItem['subtotal'] = $subtotal;
    //     $cartItem['grandTotal'] = $grandTotal;
    //     $cartItem['discountTotal'] = $discountTotal;
    //     $cartItem['gst'] = $gst;

    //     $cart_[] = $cartItem;
    //     $this->session->set('tableorder-cart_', $cart_);
    // }

    // public function cartAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $guestName = $this->TableorderSession->first_name ?? null;
    //     $mobile = $this->TableorderSession->mobile ?? null;

    //     if (!$cart_ || count($cart_)===0) {
    //         $this->flashSession->notice('Your order cart is still empty. Please purchase first. dari cart');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $subtotal = $this->refreshCart($cart_);
    //     $this->session->set('tableorder-cart_', $cart_);

    //     $canLopoUse = false;
    //     /*$IndirectOrder = IndirectOrder::findFirst([
    //         'conditions' => 'status = 0 AND fk_branch = {branchId} AND fk_customer = {fk_customer} AND loyalty_point_use > 0 '
    //             .'AND payment_sequence != \'PREPAYMENT\'',
    //         'bind' => [
    //             'branchId' => $this->Branch->id,
    //             'fk_customer' => $customer['id'],
    //         ],
    //     ]);
    //     if ($IndirectOrder && $cart_['lopoUse']) {
    //         if ($IndirectOrder->fk_ticket!==null) {
    //             $Ticket = $IndirectOrder->getTicket();
    //             if ($Ticket->paid && !$Ticket->fk_void) {
    //                 $canLopoUse = true;
    //             }
    //         }
    //     }
    //     if (!$IndirectOrder) {
    //         $canLopoUse = true;
    //     }*/

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'service' => $service,
    //         'cart_' => $cart_,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'guestName' => $guestName,
    //         'mobile' => $mobile,
    //         //'customer' => $customer,
    //         'canLopoUse' => $canLopoUse ? 1 : 0,
    //     ]);
    // }

    // public function cartUpdateAction($uniqueId)
    // {
    //     if (!$this->request->isPost()) {
    //         throw new Exception("Not found", 404);
    //     }
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $this->view->disable();

    //     $updateQty_ = $this->request->getPost('item', null, []);
    //     $updateNote_ = $this->request->getPost('note', null, []);
    //     $cart_ = $this->session->get('tableorder-cart_', []);

    //     $updatedMessage_ = [];
    //     foreach ($updateQty_ as $cartIndex => $newQty) {
    //         if (!isset($cart_[$cartIndex])) {
    //             $this->flashSession->error('Invalid update qty command.');
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //         }
    //         $cartItem = &$cart_[$cartIndex];
    //         if (intval($cartItem['qty'])===intval($newQty)) {
    //             continue;
    //         }
    //         $oldQty = intval($cartItem['qty']);
    //         $cartItem['qty'] = intval($newQty);
    //         $updatedMessage_[] = $cartItem['menuName'].' qty has updated to '.$newQty;

    //         $subtotal = $newQty/$oldQty*floatval($cartItem['subtotal']);
    //         $grandTotal = $newQty/$oldQty*floatval($cartItem['grandTotal']);
    //         $discountTotal = $subtotal-$grandTotal;
    //         $itemGst = (($cartItem['menuGst']/(1+$cartItem['menuGst'])*$cartItem['menuPrice'])+$cartItem['choiceGst'])*$newQty;
    //         $gst = round($grandTotal/$subtotal*$itemGst, 2);

    //         $cartItem['subtotal'] = $subtotal;
    //         $cartItem['grandTotal'] = $grandTotal;
    //         $cartItem['discountTotal'] = $discountTotal;
    //         $cartItem['gst'] = $gst;
    //     }
    //     foreach ($updateNote_ as $cartIndex => $newNote) {
    //         if (!isset($cart_[$cartIndex])) {
    //             $this->flashSession->error('Invalid update qty command.');
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //         }
    //         $cartItem = &$cart_[$cartIndex];
    //         $newNote = trim($newNote??'')?:null;
    //         if ($newNote===$cartItem['note']) {
    //             continue;
    //         }
    //         $cartItem['note'] = $newNote;
    //         $updatedMessage_[] = $cartItem['menuName'].' note has been updated';
    //     }

    //     foreach ($updatedMessage_ as $key => $updatedMessage) {
    //         $this->flashSession->notice($updatedMessage);
    //     }

    //     $this->session->set('tableorder-cart_', $cart_);
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    // }

    // public function cartDeleteAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $cart_ = $this->session->get('tableorder-cart_', []);

    //     $index = $this->request->getQuery('index', 'int', null);

    //     if ($index===null) {
    //         throw new Exception("Not found", 404);
    //     }

    //     if (!isset($cart_[$index])) {
    //         throw new Exception("Not found", 404);
    //     }
    //     $cartItem = $cart_[$index];
    //     array_splice($cart_, $index, 1);
    //     $this->flashSession->notice($cartItem['menuName'].' has been removed.');

    //     $this->session->set('tableorder-cart_', $cart_);
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    // }

    // public function uselopoAction()
    // {
    //     if ($this->Branch===null) {
    //         throw new Exception("Not found", 404);
    //     }
    //     if (!$this->request->isPost()) {
    //         throw new Exception("Bad Request", 400);
    //     }

    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     if (count($cart_)===0) {
    //         throw new Exception("Bad Request", 400);
    //     }

    //     $post_ = [
    //         'amount' => $this->request->getPost('amount', 'int', -1),
    //     ];
    //     $Validation = new Validation();
    //     $Validation->add([
    //         'amount',
    //         ], new VBetween([
    //         'minimum'    => [
    //             'amount' => 0,
    //         ],
    //         'maximum'    => [
    //             'amount' => 99999999,
    //         ],
    //     ]));
    //     try {
    //         $Error_ = $Validation->validate($post_);
    //     } catch(VException $e) {
    //         throw new Exception("Bad Request", 400);
    //     }
    //     if (count($Error_)>0) {
    //         throw new Exception("Bad Request", 400);
    //     }
    //     $customer = $this->session->get('customer', null);
    //     if (!$customer) {
    //         throw new Exception("Bad Request", 400);
    //     }
    //     if ($post_['amount'] > $customer['current_point']) {
    //         $this->flashSession->error('Invalid loyalty point use');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart'), true);
    //     }
    //     $subtotal = $this->refreshCart($cart_);
    //     if ($post_['amount'] > $subtotal) {
    //         //throw new Exception("Bad Request", 400);
    //         $post_['amount'] = $subtotal;
    //     }
    //     // apply promotion
    //     $cart_['lopoUse'] = $post_['amount'];
    //     $this->refreshCart($cart_);
    //     $this->session->set('cart_', $cart_);
    //     $this->flashSession->notice('Loyalty point usage');
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/order/cart'), true);
    // }

    // protected function getUserCron()
    // {
    //     $User = User::findFirst([
    //         'conditions' => 'type = {type} AND name = {name}',
    //         'bind' => [
    //             'type' => 'SYSTEM',
    //             'name' => 'CRON',
    //         ],
    //     ]);
    //     if (!$User) {
    //         return false;
    //     }
    //     return $User;
    // }

    // protected function getDeviceCron()
    // {
    //     $Device = Device::findFirst([
    //         'conditions' => 'type = {type} AND name = {name}',
    //         'bind' => [
    //             'type' => 'CRON',
    //             'name' => 'CRON',
    //         ],
    //     ]);
    //     if (!$Device) {
    //         return false;
    //     }
    //     return $Device;
    // }

    // protected function getLatestBarcode($fk_device)
    // {
    //     $Ticket = Ticket::findFirst([
    //         'conditions' => 'fk_device = {fk_device}',
    //         'bind' => [
    //             'fk_device' => $fk_device,
    //         ],
    //         'order' => 'created DESC, id DESC',
    //         /**
    //          * ini bugging karena XYYMMDD10 dan XYYMMDD9 di DESC maka muncul yang XYYMMDD9
    //          */
    //         // 'order' => 'barcode DESC, created DESC, id DESC',
    //     ]);
    //     if (!$Ticket) {
    //         return false;
    //     }
    //     return $Ticket->barcode;
    // }

    // protected function newBarcode($fk_device)
    // {
    //     $todayPrefix = $fk_device.date('ymd');
    //     $increment = 0;
    //     $latestBarcode = $this->getLatestBarcode($fk_device);
    //     if (!$latestBarcode) {
    //         $increment++;
    //         $barcode = $todayPrefix.$increment;
    //         return $barcode;
    //     }
    //     if (substr($latestBarcode, 0, strlen($todayPrefix))===$todayPrefix) {
    //         $increment = intval(str_replace($todayPrefix, '', $latestBarcode));
    //     }
    //     $increment++;
    //     $barcode = $todayPrefix.$increment;
    //     return $barcode;
    // }

    // protected function getLatestDailyNumber($fk_user, $fk_branch)
    // {
    //     $Ticket = Ticket::findFirst([
    //         'conditions' => 'fk_user = {fk_user} '.
    //             'AND fk_branch = {fk_branch} '.
    //             'AND daily_number IS NOT NULL '.
    //             'AND DATE(created) = DATE(NOW()) ',
    //         'bind' => [
    //             'fk_user' => $fk_user,
    //             'fk_branch' => $fk_branch,
    //         ],
    //         'order' => 'id DESC',
    //     ]);
    //     if (!$Ticket) {
    //         return false;
    //     }
    //     return $Ticket->daily_number;
    // }

    // protected function newDailyNumber($fk_user, $fk_branch)
    // {
    //     $increment = 0;
    //     $latestDailyNumber = $this->getLatestDailyNumber($fk_user, $fk_branch);
    //     if (!$latestDailyNumber) {
    //         $increment++;
    //         $dailyNumber = $fk_user.$increment;
    //         return $dailyNumber;
    //     }
    //     if (strpos($latestDailyNumber, $fk_user) === 0) {
    //         $increment = intval(substr_replace($latestDailyNumber, '', 0, strlen($fk_user)));
    //     }
    //     $increment++;
    //     $dailyNumber = $fk_user.$increment;
    //     return $dailyNumber;
    // }

    // protected function getMenu($fk_menu, $fk_branch)
    // {
    //     $Menu = Menu::findFirst([
    //         'conditions' => 'id = {fk_menu} AND fk_branch = {fk_branch}',
    //         'bind' => [
    //             'fk_menu' => $fk_menu,
    //             'fk_branch' => $fk_branch,
    //         ],
    //         'order' => 'id DESC',
    //     ]);
    //     if (!$Menu) {
    //         return false;
    //     }
    //     return $Menu;
    // }

    // public function checkoutAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $_guestName = $this->request->getQuery('guestName', 'string', '');
    //     $_mobile = $this->request->getQuery('mobile', 'string', '');

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $guestName = $this->TableorderSession->first_name ?? null;
    //     $mobile = $this->TableorderSession->mobile ?? null;
    //     $ticketId = $this->ticketId;

    //     if (!$cart_ || count($cart_)===0) {
    //         $this->flashSession->notice('Your order cart is still empty. Please purchase first. dari checkout');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     if (!$guestName) {
    //         $guestName = $_guestName;
    //     }

    //     if (!$mobile) {
    //         $mobile = $_mobile;
    //     }

    //     // if (!$guestName) {
    //     //     $this->flashSession->notice('Please fill customer name.');
    //     //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     // }

    //     // if (!$mobile) {
    //     //     $this->flashSession->notice('Please fill customer mobile.');
    //     //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     // }

    //     // $TableorderSession = $this->TableorderSession;
    //     // $TableorderSession->first_name = $guestName;
    //     // $TableorderSession->mobile = $mobile;
    //     // if (!$TableorderSession->save()) {
    //     //     throw new Exception(implode(', ', $TableorderSession->getMessages()), 500);
    //     // }

    //     if ($this->cfg['TABLE_ORDER_PAYMENT_SEQUENCE']==='PREPAYMENT') {
    //         if (!$this->cfg['TABLE_ORDER_USING_PINPAYMENTS'] &&
    //         !$this->cfg['TABLE_ORDER_USING_EWAY'] &&
    //         !$this->cfg['TABLE_ORDER_USING_XENDIT'] &&
    //         !$this->cfg['TABLE_ORDER_USING_FISERV'] &&
    //         !$this->cfg['TABLE_ORDER_USING_TILLPAYMENTS'] &&
    //         !$this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //             $this->flashSession->notice('We are sorry, It seems there is no payment method was set up.');
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //         }

    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     $this->db->begin();
    //     try {
    //         $this->proceedCart();
    //         $this->db->commit();
    //     } catch(Exception $e) {
    //         $this->db->rollback();
    //         throw $e;
    //     }

    //     $this->session->remove('tableorder-cart_'); // clear cart

    //     $this->flashSession->notice('Thank you, your order is being processed by our kitchen');
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    // }

    // protected function refreshCart(&$cart_)
    // {
    //     $service = $this->getService();

    //     $orderSubtotal = 0;
    //     foreach ($cart_ as $key => &$cartItem) {
    //         $discount_ = Discount::menuResult($this->Branch->id, $service['id'], $cartItem['menuId']);
    //         $cartItem['discount_'] = $discount_;

    //         $choiceTotalPerItem = $cartItem['choiceTotal'];
    //         $choiceGstPerItem = $cartItem['choiceGst'];

    //         $subtotalPerItem = $cartItem['menuPrice']+$choiceTotalPerItem;
    //         $subtotal = $cartItem['qty']*$subtotalPerItem;
    //         $grandTotalPerItem = $subtotalPerItem;

    //         if (isset($cartItem['discount_'], $cartItem['discount_']['ordinal']) && count($cartItem['discount_']['ordinal'])>0) {
    //             $discount_ = $cartItem['discount_'];
    //             for ($i=count($discount_['ordinal'])-1; $i>=0; $i--) {
    //                 $discountId = $discount_['ordinal'][$i];
    //                 $discount = $discount_[$discountId];
    //                 if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                     if ($discount['type']==='PERCENT') {
    //                         if ($discount['cap']===null) {
    //                             $grandTotalPerItem = max($grandTotalPerItem-($subtotalPerItem*$discount['percent']), 0);
    //                         } else {
    //                             $grandTotalPerItem = max($grandTotalPerItem-min($subtotalPerItem*$discount['percent'], $discount['cap']), 0);
    //                         }
    //                     } elseif ($discount['type']==='AMOUNT') {
    //                         $grandTotalPerItem = max($grandTotalPerItem - $discount['amount'], 0);
    //                     } elseif ($discount['type']==='FORCE_AMOUNT') {
    //                         $grandTotalPerItem = $discount['amount'];
    //                     }
    //                 } else {
    //                     array_splice($discount_['ordinal'], $i, 1);
    //                     unset($discount_[$discountId]);
    //                 }
    //             }
    //             $cartItem['discount_'] = $discount_;
    //         }
    //         $grandTotal = $cartItem['qty']*$grandTotalPerItem;

    //         $grandTotal = floatval($grandTotal);
    //         $discountTotal = $subtotal-$grandTotal;
    //         $itemGst = (($cartItem['menuGst']/(1+$cartItem['menuGst'])*$cartItem['menuPrice'])+$choiceGstPerItem)*$cartItem['qty'];
    //         $gst = round($grandTotal/$subtotal*$itemGst, 2);

    //         $cartItem['subtotal'] = $subtotal;
    //         $cartItem['grandTotal'] = $grandTotal;
    //         $cartItem['discountTotal'] = $discountTotal;
    //         $cartItem['gst'] = $gst;

    //         $orderSubtotal += $cartItem['grandTotal'];
    //     }
    //     // !@! apply loyalty point use
    //     /*if ($cart_['lopoUse'] > 0) {
    //         $orderSubtotal -= $cart_['lopoUse'];
    //     }
    //     $cart_['orderSubtotal'] = $orderSubtotal;*/
    //     return $orderSubtotal;
    // }

    // protected function proceedCart()
    // {
    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $guestName = $this->TableorderSession->first_name ?? null;
    //     $mobile = $this->TableorderSession->mobile ?? null;
    //     $ticketId = $this->ticketId;

    //     // refresh
    //     $subtotal = $this->refreshCart($cart_);
    //     $this->session->set('tableorder-cart_', $cart_);

    //     $openingHour = null;
    //     $postcode_ = $this->getPostcode();

    //     // harusnya pasti ada
    //     $UserCron = $this->getUserCron();
    //     $DeviceCron = $this->getDeviceCron();
    //     if (!$UserCron || !$DeviceCron) {
    //         throw new Exception("User and Device not found", 500);
    //     }
    //     $newBarcode = $this->newBarcode($DeviceCron->id);
    //     if (!$newBarcode) {
    //         throw new Exception("Can't create new barcode number", 500);
    //     }
    //     $newDailyNumber = $this->newDailyNumber($UserCron->id, $this->Branch->id);
    //     if (!$newDailyNumber) {
    //         throw new Exception("Can't create new daily number", 500);
    //     }

    //     $Ticket = null;
    //     if ($ticketId) {
    //         $Ticket = Ticket::findFirst($ticketId);
    //     }

    //     if (!$Ticket) {
    //         $Ticket                             = new Ticket();
    //         $Ticket->fk_branch                  = $this->Branch->id;
    //         $Ticket->fk_user                    = $UserCron->id;
    //         $Ticket->fk_device                  = $DeviceCron->id;
    //         $Ticket->fk_service                 = $service['id'];
    //         $Ticket->fk_staff                   = null;
    //         $Ticket->fk_customer                = null;
    //         $Ticket->fk_table                   = $table['id'] ?? null;
    //         $Ticket->fk_deliverer               = null;
    //         $Ticket->fk_ticket_split_origin     = null;
    //         $Ticket->fk_ticket_join_destination = null;
    //         $Ticket->barcode                    = $newBarcode;
    //         $Ticket->daily_number               = $newDailyNumber;
    //         $Ticket->status                     = 'OPEN';
    //         $Ticket->guest                      = $guestName;
    //         $Ticket->subtotal                   = 0; // init
    //         $Ticket->discount                   = 0; // init
    //         $Ticket->total                      = 0; // init
    //         $Ticket->customer_distance          = null;
    //         $Ticket->delivery_charge            = 0; // pasti 0
    //         $Ticket->tip                        = 0; // init
    //         $Ticket->additional_surcharge       = 0; // init
    //         $Ticket->service_charge             = 0; // init
    //         $Ticket->ppn                        = 0; // init
    //         $Ticket->grand_total                = 0; // init
    //         $Ticket->gst                        = 0; // init
    //         $Ticket->payment                    = 0; // init
    //         $Ticket->change                     = 0; // init
    //         $Ticket->note                       = null;
    //         $Ticket->information                = null;
    //         $Ticket->lopo_gained                = 0;
    //         $Ticket->created                    = date('Y-m-d H:i:s');
    //         $Ticket->updated                    = date('Y-m-d H:i:s');
    //         $Ticket->paid                       = null;
    //         $Ticket->delivering                 = null;
    //         $Ticket->delivered                  = null;
    //         $Ticket->promotion_code             = null;
    //         if ($this->cfg['TABLE_ORDER_PAYMENT_SEQUENCE']==='PREPAYMENT' &&
    //         $this->TableorderSession->payment_method==='OFFLINE') {
    //             $Ticket->auto_print_kitchen = 0;
    //         } else {
    //             $Ticket->auto_print_kitchen = 1;
    //         }
    //         if (!$Ticket->save()) {
    //             throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //         }
    //     }

    //     $subtotal = $Ticket->subtotal ?? 0;
    //     $discountTotal = $Ticket->discount ?? 0;
    //     $total = $Ticket->total ?? 0; // grandTotal item
    //     $deliveryCharge = $Ticket->delivery_charge ?? 0; // pasti 0
    //     $additionalSurcharge = $Ticket->additional_surcharge ?? 0;
    //     $serviceCharge = $Ticket->service_charge ?? 0;
    //     $ppn = $Ticket->ppn ?? 0;
    //     $grandTotal = $Ticket->grand_total ?? 0; // grandTotal item + delivery charge + additional surcharge
    //     $gstTotal = $Ticket->gst ?? 0;

    //     foreach ($cart_ as $key1 => $cartItem) {
    //         $subtotal += floatval($cartItem['grandTotal']);
    //         $gstTotal += floatval($cartItem['gst']);

    //         $Menu = $this->getMenu($cartItem['menuId'], $this->Branch->id);
    //         $fk_printer_group = $Menu->fk_printer_group ?: $this->cfg['DEFAULT_MENU_PRINTER_GROUP'];

    //         $TicketItem = new TicketItem();
    //         $TicketItem->fk_ticket = $Ticket->id;
    //         $TicketItem->fk_ticket_item = null;
    //         $TicketItem->fk_user = $UserCron->id;
    //         $TicketItem->fk_device = $DeviceCron->id;
    //         $TicketItem->fk_menu = $cartItem['menuId'] ?? null;
    //         $TicketItem->fk_printer_group = $fk_printer_group ?: null;
    //         $TicketItem->person = 1;
    //         $TicketItem->menu_name = $cartItem['menuName'];
    //         $TicketItem->menu_gst = $cartItem['menuGst'];
    //         $TicketItem->menu_price = $cartItem['menuPrice'];
    //         $TicketItem->menu_open = 0; // pasti 0
    //         $TicketItem->menu_package = $cartItem['menuPackage'];
    //         $TicketItem->qty = $cartItem['qty'];
    //         $TicketItem->choice_total = 0; // per item (qty = 1)
    //         $TicketItem->choice_gst = 0; // per item (qty = 1)
    //         $TicketItem->subtotal = 0;
    //         $TicketItem->discount = 0;
    //         $TicketItem->grand_total = 0;
    //         $TicketItem->gst = 0;
    //         $TicketItem->note = $cartItem['note'];
    //         $TicketItem->created = date('Y-m-d H:i:s');
    //         $TicketItem->updated = date('Y-m-d H:i:s');
    //         if (!$TicketItem->save()) {
    //             throw new Exception(implode(', ', $TicketItem->getMessages()), 500);
    //         }

    //         // Decrease Stock
    //         $parentMultiplier = 1;
    //         $menuMovement = intval($TicketItem->qty) * $parentMultiplier;
    //         if (isset($TicketItem->fk_menu)) {
    //             $Menu = Menu::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch}',
    //                 'bind'       => [
    //                     'id'        => $TicketItem->fk_menu,
    //                     'fk_branch' => $this->Branch->id,
    //                 ],
    //             ]);
    //             if ($Menu && $Menu->stock !== null) {
    //                 $Menu->stock -= $menuMovement;
    //                 if (!$Menu->update()) {
    //                     throw new Exception(implode(', ', $Menu->getMessages()), 500);
    //                 }
    //                 $StockMovement                  = new StockMovement();
    //                 $StockMovement->fk_user         = $UserCron->id;
    //                 $StockMovement->fk_device       = $DeviceCron->id;
    //                 $StockMovement->fk_menu         = $Menu->id;
    //                 $StockMovement->fk_ticket_item  = $TicketItem->id;
    //                 $StockMovement->type            = 'TRANSACTION';
    //                 $StockMovement->qty             = -1 * $menuMovement;
    //                 if (!$StockMovement->create()) {
    //                     throw new Exception(implode(', ', $StockMovement->getMessages()), 500);
    //                 }
    //             }
    //         }// End IF Decrease Stock
    //         $utemp = IngredientMovement::createFromTicketItem($TicketItem, $UserCron->id, $DeviceCron->id, $menuMovement);

    //         if (isset($cartItem['choiceItem_']) && count($cartItem['choiceItem_'])) {
    //             foreach ($cartItem['choiceItem_'] as $key2 => $choiceItem)  {
    //                 $TicketItemChoice = new TicketItemChoice();
    //                 $TicketItemChoice->fk_ticket_item = $TicketItem->id;
    //                 $TicketItemChoice->fk_user = $UserCron->id;
    //                 $TicketItemChoice->fk_device = $DeviceCron->id;
    //                 $TicketItemChoice->fk_choice_group = $choiceItem['choiceGroupId'] ?? null;
    //                 $TicketItemChoice->choice_group_name = $choiceItem['choiceGroupName'];
    //                 $TicketItemChoice->fk_choice = $choiceItem['choiceId'] ?? null;
    //                 $TicketItemChoice->choice_name = $choiceItem['choiceName'];
    //                 $TicketItemChoice->choice_gst = $choiceItem['choiceGst'];
    //                 $TicketItemChoice->choice_price = $choiceItem['choicePrice'];
    //                 $TicketItemChoice->choice_open = 0; //pasti 0
    //                 $TicketItemChoice->choice_default_qty = $choiceItem['choiceDefaultQty'] ?? null;
    //                 $TicketItemChoice->qty = $choiceItem['qty'];
    //                 $TicketItemChoice->total = $choiceItem['total'];
    //                 $TicketItemChoice->gst = $choiceItem['gst'];
    //                 $TicketItemChoice->created = date('Y-m-d H:i:s');
    //                 $TicketItemChoice->updated = date('Y-m-d H:i:s');
    //                 if (!$TicketItemChoice->save()) {
    //                     throw new Exception(implode(", ", $TicketItemChoice->getMessages()), 500);
    //                 }

    //                 // Decrease Stock
    //                 $choiceMovement = intval($TicketItemChoice->qty) * $menuMovement;
    //                 if (isset($TicketItemChoice->fk_choice)) {
    //                     $Choice = Choice::findFirst([
    //                         'conditions' => 'id = {id} AND fk_branch = {fk_branch}',
    //                         'bind'       => [
    //                             'id'        => $TicketItemChoice->fk_choice,
    //                             'fk_branch' => $this->Branch->id,
    //                         ],
    //                     ]);
    //                     if ($Choice && $Choice->stock !== null) {
    //                         $Choice->stock -= $choiceMovement;
    //                         if (!$Choice->update()) {
    //                             throw new Exception(implode(', ', $Choice->getMessages()), 500);
    //                         }
    //                         $StockMovement                          = new StockMovement();
    //                         $StockMovement->fk_user                 = $UserCron->id;
    //                         $StockMovement->fk_device               = $DeviceCron->id;
    //                         $StockMovement->fk_choice               = $Choice->id;
    //                         $StockMovement->fk_ticket_item_choice   = $TicketItemChoice->id;
    //                         $StockMovement->type                    = 'TRANSACTION';
    //                         $StockMovement->qty                     = -1 * $choiceMovement;
    //                         if (!$StockMovement->create()) {
    //                             throw new Exception(implode(', ', $StockMovement->getMessages()), 500);
    //                         }
    //                     }
    //                 } // END IF Decrease Stock
    //                 $utemp = IngredientMovement::createFromTicketItemChoice($TicketItemChoice, $UserCron->id, $DeviceCron->id, $choiceMovement);
    //             }
    //         } // end of TicketItemChoice

    //         if (isset($cartItem['child_']) && count($cartItem['child_'])) {
    //             foreach($cartItem['child_'] as $key2 => $childItem) {
    //                 $MenuChild = $this->getMenu($childItem['menuId'], $this->Branch->id);
    //                 $fk_printer_group_child = $MenuChild->fk_printer_group ?: $this->cfg['DEFAULT_MENU_PRINTER_GROUP'];

    //                 $ChildItem = new TicketItem();
    //                 $ChildItem->fk_ticket = $Ticket->id;
    //                 $ChildItem->fk_ticket_item = $TicketItem->id;
    //                 $ChildItem->fk_user = $UserCron->id;
    //                 $ChildItem->fk_device = $DeviceCron->id;
    //                 $ChildItem->fk_menu = $childItem['menuId'] ?? null;
    //                 $ChildItem->fk_printer_group = $fk_printer_group_child;
    //                 $ChildItem->person = 1;
    //                 $ChildItem->menu_name = $childItem['menuName'];
    //                 $ChildItem->menu_gst = $childItem['menuGst'];
    //                 $ChildItem->menu_price = $childItem['menuPrice'];
    //                 $ChildItem->menu_open = 0; // pasti 0
    //                 $ChildItem->menu_package = 0;
    //                 $ChildItem->qty = $childItem['qty'];
    //                 $ChildItem->choice_total = 0; // per item (qty = 1)
    //                 $ChildItem->choice_gst = 0; // per item (qty = 1)
    //                 $ChildItem->subtotal = 0;
    //                 $ChildItem->discount = 0;
    //                 $ChildItem->grand_total = 0;
    //                 $ChildItem->gst = 0;
    //                 $ChildItem->created = date('Y-m-d H:i:s');
    //                 $ChildItem->updated = date('Y-m-d H:i:s');
    //                 if (!$ChildItem->save()) {
    //                     throw new Exception(implode(', ', $ChildItem->getMessages()), 500);
    //                 }

    //                 // Decrease Stock
    //                 $parentMultiplier = intval($TicketItem->qty);
    //                 $menuMovement = intval($ChildItem->qty) * $parentMultiplier;
    //                 if (isset($ChildItem->fk_menu)) {
    //                     $Menu = Menu::findFirst([
    //                         'conditions' => 'id = {id} AND fk_branch = {fk_branch}',
    //                         'bind'       => [
    //                             'id'        => $ChildItem->fk_menu,
    //                             'fk_branch' => $this->Branch->id,
    //                         ],
    //                     ]);
    //                     if ($Menu && $Menu->stock !== null) {
    //                         $Menu->stock -= $menuMovement;
    //                         if (!$Menu->update()) {
    //                             throw new Exception(implode(', ', $Menu->getMessages()), 500);
    //                         }
    //                         $StockMovement                  = new StockMovement();
    //                         $StockMovement->fk_user         = $UserCron->id;
    //                         $StockMovement->fk_device       = $DeviceCron->id;
    //                         $StockMovement->fk_menu         = $Menu->id;
    //                         $StockMovement->fk_ticket_item  = $ChildItem->id;
    //                         $StockMovement->type            = 'TRANSACTION';
    //                         $StockMovement->qty             = -1 * $menuMovement;
    //                         if (!$StockMovement->create()) {
    //                             throw new Exception(implode(', ', $StockMovement->getMessages()), 500);
    //                         }
    //                     }
    //                 }// End IF Decrease Stock
    //                 $utemp = IngredientMovement::createFromTicketItem($ChildItem, $UserCron->id, $DeviceCron->id, $menuMovement);

    //                 $childChoiceTotalPerItem = 0;
    //                 $childChoiceGstPerItem = 0;

    //                 if (isset($childItem['choiceItem_']) && count($childItem['choiceItem_'])) {
    //                     foreach ($childItem['choiceItem_'] as $key3 => $choiceItem)  {
    //                         $ChildItemChoice = new TicketItemChoice();
    //                         $ChildItemChoice->fk_ticket_item = $ChildItem->id;
    //                         $ChildItemChoice->fk_user = $UserCron->id;
    //                         $ChildItemChoice->fk_device = $DeviceCron->id;
    //                         $ChildItemChoice->fk_choice_group = $choiceItem['choiceGroupId'] ?? null;
    //                         $ChildItemChoice->choice_group_name = $choiceItem['choiceGroupName'];
    //                         $ChildItemChoice->fk_choice = $choiceItem['choiceId'] ?? null;
    //                         $ChildItemChoice->choice_name = $choiceItem['choiceName'];
    //                         $ChildItemChoice->choice_gst = $choiceItem['choiceGst'];
    //                         $ChildItemChoice->choice_price = $choiceItem['choicePrice'];
    //                         $ChildItemChoice->choice_open = 0; //pasti 0
    //                         $ChildItemChoice->choice_default_qty = $choiceItem['choiceDefaultQty'] ?? null;
    //                         $ChildItemChoice->qty = $choiceItem['qty'];
    //                         $ChildItemChoice->total = $choiceItem['total'];
    //                         $ChildItemChoice->gst = $choiceItem['gst'];
    //                         $ChildItemChoice->note = null;
    //                         $ChildItemChoice->created = date('Y-m-d H:i:s');
    //                         $ChildItemChoice->updated = date('Y-m-d H:i:s');
    //                         if (!$ChildItemChoice->save()) {
    //                             throw new Exception(implode(", ", $ChildItemChoice->getMessages()), 500);
    //                         }

    //                         $childChoiceTotalPerItem += floatval($choiceItem['total']);
    //                         $childChoiceGstPerItem += floatval($choiceItem['gst']);


    //                         // Decrease Stock
    //                         $choiceMovement = intval($ChildItemChoice->qty) * $menuMovement;
    //                         if (isset($ChildItemChoice->fk_choice)) {
    //                             $Choice = Choice::findFirst([
    //                                 'conditions' => 'id = {id} AND fk_branch = {fk_branch}',
    //                                 'bind'       => [
    //                                     'id'        => $ChildItemChoice->fk_choice,
    //                                     'fk_branch' => $this->Branch->id,
    //                                 ],
    //                             ]);
    //                             if ($Choice && $Choice->stock !== null) {
    //                                 $Choice->stock -= $choiceMovement;
    //                                 if (!$Choice->update()) {
    //                                     throw new Exception(implode(', ', $Choice->getMessages()), 500);
    //                                 }
    //                                 $StockMovement                          = new StockMovement();
    //                                 $StockMovement->fk_user                 = $UserCron->id;
    //                                 $StockMovement->fk_device               = $DeviceCron->id;
    //                                 $StockMovement->fk_choice               = $Choice->id;
    //                                 $StockMovement->fk_ticket_item_choice   = $ChildItemChoice->id;
    //                                 $StockMovement->type                    = 'TRANSACTION';
    //                                 $StockMovement->qty                     = -1 * $choiceMovement;
    //                                 if (!$StockMovement->create()) {
    //                                     throw new Exception(implode(', ', $StockMovement->getMessages()), 500);
    //                                 }
    //                             }
    //                         } // END IF Decrease Stock
    //                         $utemp = IngredientMovement::createFromTicketItemChoice($ChildItemChoice, $UserCron->id, $DeviceCron->id, $choiceMovement);
    //                     }
    //                 }// end of ChildItemChoice

    //                 $ChildItem->choice_total = floatval($childChoiceTotalPerItem);
    //                 $ChildItem->choice_gst = floatval($childChoiceGstPerItem);
    //                 if (!$ChildItem->save()) {
    //                     throw new Exception(implode(', ', $ChildItem->getMessages()), 500);
    //                 }
    //             }
    //         } // end of ChildItem

    //         $subtotalPerItem = $cartItem['menuPrice']+$cartItem['choiceTotal'];
    //         $grandTotalPerItem = $subtotalPerItem;

    //         if (isset($cartItem['discount_'], $cartItem['discount_']['ordinal']) && count($cartItem['discount_']['ordinal'])>0) {
    //             $discount_ = $cartItem['discount_'];
    //             foreach ($discount_['ordinal'] as $key2 => $discountId) {
    //                 $discount = $discount_[$discountId];
    //                 $valueBefore = $grandTotalPerItem;
    //                 if (count($discount['rule_']['ordinal'])===0 || $this->ruleValidate($discount['rule_'])) {
    //                     if ($discount['type']==='PERCENT') {
    //                         if ($discount['cap']===null) {
    //                             $grandTotalPerItem = max($grandTotalPerItem-($subtotalPerItem*$discount['percent']), 0);
    //                         } else {
    //                             $grandTotalPerItem = max($grandTotalPerItem-min($subtotalPerItem*$discount['percent'], $discount['cap']), 0);
    //                         }
    //                     } elseif ($discount['type']==='AMOUNT') {
    //                         $grandTotalPerItem = max($grandTotalPerItem - $discount['amount'], 0);
    //                     } elseif ($discount['type']==='FORCE_AMOUNT') {
    //                         $grandTotalPerItem = $discount['amount'];
    //                     }
    //                 }
    //                 $valueAfter = $grandTotalPerItem;
    //                 $discountAmount = $valueBefore-$valueAfter;

    //                 $TicketItemDiscount = new TicketItemDiscount();
    //                 $TicketItemDiscount->fk_ticket_item = $TicketItem->id;
    //                 $TicketItemDiscount->fk_user = $UserCron->id;
    //                 $TicketItemDiscount->fk_device = $DeviceCron->id;
    //                 $TicketItemDiscount->fk_discount = $discount['id'];
    //                 $TicketItemDiscount->discount_name = $discount['name'];
    //                 $TicketItemDiscount->discount_type = $discount['type'];
    //                 $TicketItemDiscount->discount_amount = $discount['amount'] ?? null;
    //                 $TicketItemDiscount->discount_percent = $discount['percent'] ?? null;
    //                 $TicketItemDiscount->discount_cap = $discount['cap'] ?? null;
    //                 $TicketItemDiscount->total = $discountAmount;
    //                 $TicketItemDiscount->created = date('Y-m-d H:i:s');
    //                 $TicketItemDiscount->updated = date('Y-m-d H:i:s');
    //                 if (!$TicketItemDiscount->save()) {
    //                     throw new Exception(implode(", ", $TicketItemDiscount->getMessages()), 500);
    //                 }
    //             }
    //         } // end of TicketItemDiscount

    //         $discountTotal += $cartItem['discountTotal'];

    //         $TicketItem->choice_total = $cartItem['choiceTotal'];
    //         $TicketItem->choice_gst = $cartItem['choiceGst'];
    //         $TicketItem->subtotal = $cartItem['subtotal'];
    //         $TicketItem->discount = $cartItem['discountTotal'];
    //         $TicketItem->grand_total = $cartItem['grandTotal'];
    //         $TicketItem->gst = $cartItem['gst'];
    //         if (!$TicketItem->save()) {
    //             throw new Exception(implode(', ', $TicketItem->getMessages()), 500);
    //         }
    //     } // end of TicketItem

    //     $serviceChargePercentage = 0;
    //     if (
    //       ($service['name']==='DELIVERY' && intval($this->cfg['SERVICE_CHARGE_DELIVERY']??1)>0) ||
    //       ($service['name']==='DINE IN' && intval($this->cfg['SERVICE_CHARGE_DINE_IN']??1)>0) ||
    //       ($service['name']==='TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - DELIVERY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_DELIVERY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='TABLE ORDER' && intval($this->cfg['SERVICE_CHARGE_TABLE_ORDER']??1)>0) ||
    //       ($service['name']==='GENERAL' && intval($this->cfg['SERVICE_CHARGE_GENERAL']??1)>0)
    //     ) {
    //       $serviceChargePercentage = $this->cfg['SERVICE_CHARGE'] ?? 0;
    //     }

    //     $total = $subtotal-$discountTotal;
    //     $serviceCharge = 0;
    //     if ($serviceChargePercentage>0) {
    //         $serviceCharge = $serviceChargePercentage*$total;
    //     }
    //     $ppn = 0;
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $ppn = $this->cfg['PPN']*($total+$serviceCharge);
    //     }
    //     $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn;

    //     $Ticket->subtotal = $subtotal;
    //     $Ticket->discount = $discountTotal;
    //     $Ticket->total = $total;
    //     $Ticket->delivery_charge = $deliveryCharge;
    //     $Ticket->additional_surcharge = $additionalSurcharge;
    //     $Ticket->service_charge = $serviceCharge;
    //     $Ticket->ppn = $ppn;
    //     $Ticket->grand_total = $grandTotal;
    //     $Ticket->gst = $gstTotal;
    //     $billAmount = $Ticket->grand_total - $Ticket->payment;
    //     if ($billAmount>0) {
    //         $Ticket->paid = null;
    //     }
    //     $Ticket->updated = date('Y-m-d H:i:s');
    //     if (!$Ticket->save()) {
    //         throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //     }

    //     $TableorderSession = $this->TableorderSession;
    //     if (!$TableorderSession->fk_ticket) {
    //         $TableorderSession->fk_ticket = $Ticket->id;
    //         if (!$TableorderSession->save()) {
    //             throw new Exception(implode(', ', $TableorderSession->getMessages()), 500);
    //         }
    //     }

    //     return $Ticket;
    // }

    // public function prepaymentMethodAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $ticketId = $this->ticketId;
    //     $tableorderSession = TableorderSession::prepareArray($this->TableorderSession);

    //     $subtotal =  $this->refreshCart($cart_);
    //     $discountTotal = 0;
    //     $total = $subtotal-$discountTotal;
    //     $deliveryCharge = 0;
    //     $additionalSurcharge = 0;

    //     $serviceChargePercentage = 0;
    //     if (
    //       ($service['name']==='DELIVERY' && intval($this->cfg['SERVICE_CHARGE_DELIVERY']??1)>0) ||
    //       ($service['name']==='DINE IN' && intval($this->cfg['SERVICE_CHARGE_DINE_IN']??1)>0) ||
    //       ($service['name']==='TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - DELIVERY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_DELIVERY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='TABLE ORDER' && intval($this->cfg['SERVICE_CHARGE_TABLE_ORDER']??1)>0) ||
    //       ($service['name']==='GENERAL' && intval($this->cfg['SERVICE_CHARGE_GENERAL']??1)>0)
    //     ) {
    //       $serviceChargePercentage = $this->cfg['SERVICE_CHARGE'] ?? 0;
    //     }

    //     $serviceCharge = 0;
    //     if ($serviceChargePercentage>0) {
    //         $serviceCharge = $serviceChargePercentage*$total;
    //     }
    //     $ppn = 0;
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $ppn = $this->cfg['PPN']*($total+$serviceCharge);
    //     }
    //     $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn;

    //     $billAmount = $grandTotal;
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }

    //     if (!$this->cfg['TABLE_ORDER_USING_PINPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_EWAY'] &&
    //     !$this->cfg['TABLE_ORDER_USING_XENDIT'] &&
    //     !$this->cfg['TABLE_ORDER_USING_FISERV'] &&
    //     !$this->cfg['TABLE_ORDER_USING_TILLPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //         $this->flashSession->notice('We are sorry, It seems there is no payment method was set up.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }

    //     $paymentMethod_ = [];
    //     if ($this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //         $paymentMethod_[] = [
    //             'code' => 'pinpayments',
    //             'label' => 'Credit Card via Pinpayments',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_EWAY']) {
    //         $paymentMethod_[] = [
    //             'code' => 'eway',
    //             'label' => 'Credit Card via Eway',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //         $paymentMethod_[] = [
    //             'code' => 'xendit',
    //             'label' => 'QRIS/Credit card/Debit',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_FISERV']) {
    //         $paymentMethod_[] = [
    //             'code' => 'fiserv',
    //             'label' => 'Credit Card via Fiserv',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //         $paymentMethod_[] = [
    //             'code' => 'tillpayments',
    //             'label' => 'Credit Card via Tillpayments',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //         $paymentMethod_[] = [
    //             'code' => 'offline',
    //             'label' => 'Pay on station',
    //         ];
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'billAmount' => $billAmount,
    //         'tableorderSession' => $tableorderSession,
    //         'paymentMethod_' => $paymentMethod_,
    //     ]);
    // }

    // public function prepaymentMethodSubmitAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $ticketId = $this->ticketId;
    //     $tableorderSession = TableorderSession::prepareArray($this->TableorderSession);

    //     $subtotal =  $this->refreshCart($cart_);
    //     $discountTotal = 0;
    //     $total = $subtotal-$discountTotal;
    //     $deliveryCharge = 0;
    //     $additionalSurcharge = 0;

    //     $serviceChargePercentage = 0;
    //     if (
    //       ($service['name']==='DELIVERY' && intval($this->cfg['SERVICE_CHARGE_DELIVERY']??1)>0) ||
    //       ($service['name']==='DINE IN' && intval($this->cfg['SERVICE_CHARGE_DINE_IN']??1)>0) ||
    //       ($service['name']==='TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - DELIVERY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_DELIVERY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='TABLE ORDER' && intval($this->cfg['SERVICE_CHARGE_TABLE_ORDER']??1)>0) ||
    //       ($service['name']==='GENERAL' && intval($this->cfg['SERVICE_CHARGE_GENERAL']??1)>0)
    //     ) {
    //       $serviceChargePercentage = $this->cfg['SERVICE_CHARGE'] ?? 0;
    //     }

    //     $serviceCharge = 0;
    //     if ($serviceChargePercentage>0) {
    //         $serviceCharge = $serviceChargePercentage*$total;
    //     }
    //     $ppn = 0;
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $ppn = $this->cfg['PPN']*($total+$serviceCharge);
    //     }
    //     $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn;

    //     $billAmount = $grandTotal;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $billAmount;
    //         $billAmount = $this->tool->rounding($billAmount);
    //         $rounding = $billAmount-$beforeRounding;
    //         $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn+$rounding;
    //     }
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }

    //     if (!$this->cfg['TABLE_ORDER_USING_PINPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_EWAY'] &&
    //     !$this->cfg['TABLE_ORDER_USING_XENDIT'] &&
    //     !$this->cfg['TABLE_ORDER_USING_FISERV'] &&
    //     !$this->cfg['TABLE_ORDER_USING_TILLPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //         $this->flashSession->notice('We are sorry, It seems there is no payment method was set up.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }

    //     $firstname = $this->request->getPost('firstname', 'string', '');
    //     $lastname = $this->request->getPost('lastname', 'string', '');
    //     $mobile = $this->request->getPost('mobile', 'string', '');
    //     $email = $this->request->getPost('email', 'email', '');
    //     $method = $this->request->getPost('method', 'string', '');

    //     $post = [
    //         'firstname' => $firstname,
    //         'lastname' => $lastname,
    //         'mobile' => $mobile,
    //         'email' => $email,
    //         'method' => $method,
    //     ];

    //     $Validation = new Validation();
    //     $Validation->add([
    //         'firstname',
    //     ], new VStringLength([
    //         'min' => [
    //             'firstname' => 1,
    //         ],
    //         'max' => [
    //             'firstname' => 100,
    //         ],
    //     ]));
    //     $Validation->add([
    //         'lastname',
    //     ], new VStringLength([
    //         'min' => [
    //             'lastname' => 1,
    //         ],
    //         'max' => [
    //             'lastname' => 100,
    //         ],
    //         'allowEmpty' => true,
    //     ]));
    //     $Validation->add([
    //         'mobile',
    //     ], new VRegex([
    //         'pattern' => [
    //             'mobile' => $this->config->regex->mobile->{$this->Branch->country},
    //         ],
    //         'allowEmpty' => $method==='offline',
    //     ]));
    //     $Validation->add([
    //         'email',
    //     ], new VEmail([
    //         'allowEmpty' => true,
    //     ]));
    //     $Validation->add([
    //         'method',
    //     ], new VInclusionIn([
    //         'domain' => [
    //             'method' => ['pinpayments', 'eway', 'xendit', 'fiserv', 'tillpayments', 'offline'],
    //         ],
    //     ]));
    //     try {
    //         $Error_ = $Validation->validate($post);
    //     } catch(VException $e) {
    //         $this->flashSession->error($e->getMessage());
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }
    //     if (count($Error_)) {
    //         foreach ($Error_ as $key => $Error) {
    //             $this->flashSession->warning($Error->getMessage());
    //         }
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     $TableorderSession = $this->TableorderSession;
    //     $TableorderSession->first_name = $firstname;
    //     $TableorderSession->last_name = $lastname ?? '';
    //     $TableorderSession->mobile = $mobile ?? '';
    //     $TableorderSession->email = $email ?? '';
    //     $TableorderSession->payment_method = strtoupper($method);
    //     if (!$TableorderSession->save()) {
    //         throw new Exception(implode(',', $TableorderSession->getMessages()), 500);
    //     }

    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    // }

    // public function prepaymentAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $ticketId = $this->ticketId;
    //     $TableorderSession = $this->TableorderSession;

    //     if (empty($TableorderSession->payment_method) || empty($TableorderSession->first_name)) {
    //         $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     if ($TableorderSession->payment_method!=='OFFLINE' && empty($TableorderSession->mobile)) {
    //         $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     $tableorderSession = TableorderSession::prepareArray($TableorderSession);
    //     $paymentMethod = $TableorderSession->payment_method;

    //     if (!$paymentMethod || !in_array($paymentMethod, ['PINPAYMENTS', 'EWAY', 'XENDIT', 'FISERV', 'TILLPAYMENTS', 'OFFLINE'])) {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $subtotal =  $this->refreshCart($cart_);
    //     $discountTotal = 0;
    //     $total = $subtotal-$discountTotal;
    //     $deliveryCharge = 0;
    //     $additionalSurcharge = 0;

    //     $serviceChargePercentage = 0;
    //     if (
    //       ($service['name']==='DELIVERY' && intval($this->cfg['SERVICE_CHARGE_DELIVERY']??1)>0) ||
    //       ($service['name']==='DINE IN' && intval($this->cfg['SERVICE_CHARGE_DINE_IN']??1)>0) ||
    //       ($service['name']==='TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - DELIVERY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_DELIVERY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='TABLE ORDER' && intval($this->cfg['SERVICE_CHARGE_TABLE_ORDER']??1)>0) ||
    //       ($service['name']==='GENERAL' && intval($this->cfg['SERVICE_CHARGE_GENERAL']??1)>0)
    //     ) {
    //       $serviceChargePercentage = $this->cfg['SERVICE_CHARGE'] ?? 0;
    //     }

    //     $serviceCharge = 0;
    //     if ($serviceChargePercentage>0) {
    //         $serviceCharge = $serviceChargePercentage*$total;
    //     }
    //     $ppn = 0;
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $ppn = $this->cfg['PPN']*($total+$serviceCharge);
    //     }
    //     $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn;

    //     $billAmount = $grandTotal;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $billAmount;
    //         $billAmount = $this->tool->rounding($billAmount);
    //         $rounding = $billAmount-$beforeRounding;
    //         $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn+$rounding;
    //     }
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0. dari prepayment');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }
    //     if (!$this->cfg['TABLE_ORDER_USING_PINPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_EWAY'] &&
    //     !$this->cfg['TABLE_ORDER_USING_XENDIT'] &&
    //     !$this->cfg['TABLE_ORDER_USING_FISERV'] &&
    //     !$this->cfg['TABLE_ORDER_USING_TILLPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //         $this->flashSession->notice('We are sorry, It seems there is no payment method was set up.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/cart/'.$uniqueId), true);
    //     }

    //     if ($paymentMethod==='PINPAYMENTS' && $this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //     } else if ($paymentMethod==='EWAY' && $this->cfg['TABLE_ORDER_USING_EWAY']) {
    //     } else if ($paymentMethod==='XENDIT' && $this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //         // XENDIT
    //         $APIConfig = $this->config->api->{$this->config->status}->XENDIT;
    //         Xendit::setApiKey($APIConfig['SecretKey']);

    //         $this->db->begin();
    //         try {
    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = null;
    //             $OnlinePayment->fk_ticket_payment = null;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = 'XENDIT';
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             $OnlinePayment->fiserv_status = null;
    //             $OnlinePayment->fiserv_approval_code = null;
    //             $OnlinePayment->fiserv_response_hash = null;
    //             $OnlinePayment->fiserv_refnumber = null;
    //             $OnlinePayment->fiserv_ipg_transaction_id = null;
    //             $OnlinePayment->fiserv_terminal_id = null;
    //             $OnlinePayment->fiserv_scheme_transaction_id = null;
    //             $OnlinePayment->fiserv_response = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $params = [
    //                 'external_id' => 'Payment #'.$OnlinePayment->id,
    //                 // 'payer_email' => $TableorderSession->email,
    //                 'description' => 'Table Order Pre-payment',
    //                 'amount' => $billAmount,
    //                 //
    //                 'for-user-id' => $this->cfg['XENDIT_ACCOUNT_ID'],
    //                 'success_redirect_url' => $this->absoluteUrl->get($this->branchSlug.'/tableorder/precharge/'.$uniqueId.'/?payment_id='.$OnlinePayment->id),
    //                 'failure_redirect_url' => $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId),
    //             ];
    //             $createInvoice = \Xendit\Invoice::create($params);

    //             $createInvoiceJson = json_encode($createInvoice);

    //             $OnlinePayment->xendit_invoice_id = $createInvoice['id'];
    //             $OnlinePayment->xendit_invoice_status = $createInvoice['status'];
    //             $OnlinePayment->xendit_create_invoice_json = $createInvoiceJson;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $this->db->commit();
    //             return $this->response->redirect($createInvoice['invoice_url'], true);
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             $this->flashSession->error($e->getMessage());
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //         }
    //     } else if ($paymentMethod==='FISERV' && $this->cfg['TABLE_ORDER_USING_FISERV']) {
    //         // FISERV
    //         $APIConfig = $this->getFiservConfig();

    //         $this->db->begin();
    //         try {
    //             $TableorderSession->temp = json_encode($cart_);
    //             if (!$TableorderSession->save()) {
    //                 throw new Exception(implode($TableorderSession->getMessages()), 500);
    //             }

    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = null;
    //             $OnlinePayment->fk_ticket_payment = null;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = 'FISERV';
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             $OnlinePayment->fiserv_status = null;
    //             $OnlinePayment->fiserv_approval_code = null;
    //             $OnlinePayment->fiserv_response_hash = null;
    //             $OnlinePayment->fiserv_refnumber = null;
    //             $OnlinePayment->fiserv_ipg_transaction_id = null;
    //             $OnlinePayment->fiserv_terminal_id = null;
    //             $OnlinePayment->fiserv_scheme_transaction_id = null;
    //             $OnlinePayment->fiserv_response = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $MyFiserv = new \Wisdom\MyFiserv($APIConfig, $this->config->timezone);

    //             $fiserv = $MyFiserv->prepare(
    //                 $OnlinePayment->total,
    //                 'Prepayment Session-'.$TableorderSession->id.'@'.strtotime($OnlinePayment->created),
    //                 $OnlinePayment->created,
    //                 $this->absoluteUrl->get($this->branchSlug.'/tableorder/precharge/'.$uniqueId.'?payment_id='.$OnlinePayment->id),
    //                 $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId.'?fiserv=fail')
    //             );

    //             $this->db->commit();
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             // $this->flashSession->error($e->getMessage());
    //             Tool::predump($e);
    //             exit;
    //         }

    //         $this->view->setVars([
    //             'fiserv' => $fiserv,
    //         ]);
    //     } else if ($paymentMethod==='TILLPAYMENTS' && $this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //         // TILLPAYMENTS
    //         $APIConfig = $this->config->api->{$this->config->status}->TILLPAYMENTS;

    //         $config = (object) [
    //             'apiKey' => $this->cfg['TILLPAYMENTS_API_KEY'],
    //             'apiSharedSecret' => $this->cfg['TILLPAYMENTS_API_SHARED_SECRET'],
    
    //             'apiUsername' =>  $this->cfg['TILLPAYMENTS_API_USERNAME'],
    //             'apiPassword' =>  $this->cfg['TILLPAYMENTS_API_PASSWORD'],
    //             'publicIntegrationKey' => $this->cfg['TILLPAYMENTS_API_PUBLIC_INTEGRATION_KEY'],
    
    //             'endpoint' => $APIConfig->endpoint,
    //         ];
            
    //         $this->db->begin();
    //         try {
    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = null;
    //             $OnlinePayment->fk_ticket_payment = null;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = 'TILLPAYMENTS';
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             $OnlinePayment->fiserv_status = null;
    //             $OnlinePayment->fiserv_approval_code = null;
    //             $OnlinePayment->fiserv_response_hash = null;
    //             $OnlinePayment->fiserv_refnumber = null;
    //             $OnlinePayment->fiserv_ipg_transaction_id = null;
    //             $OnlinePayment->fiserv_terminal_id = null;
    //             $OnlinePayment->fiserv_scheme_transaction_id = null;
    //             $OnlinePayment->fiserv_response = null;
    //             $OnlinePayment->tillpayments_preauthorize_merchant_transaction_id = null;
    //             $OnlinePayment->tillpayments_preauthorize_datetime = null;
    //             $OnlinePayment->tillpayments_preauthorize_signature = null;
    //             $OnlinePayment->tillpayments_preauthorize_success = null;
    //             $OnlinePayment->tillpayments_preauthorize_uuid = null;
    //             $OnlinePayment->tillpayments_preauthorize_purchase_id = null;
    //             $OnlinePayment->tillpayments_preauthorize_redirect_url = null;
    //             $OnlinePayment->tillpayments_preauthorize_response = null;
    //             $OnlinePayment->tillpayments_capture_merchant_transaction_id = null;
    //             $OnlinePayment->tillpayments_capture_datetime = null;
    //             $OnlinePayment->tillpayments_capture_signature = null;
    //             $OnlinePayment->tillpayments_capture_success = null;
    //             $OnlinePayment->tillpayments_capture_uuid = null;
    //             $OnlinePayment->tillpayments_capture_purchase_id = null;
    //             $OnlinePayment->tillpayments_capture_response = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }
                
    //             $txnId = 'p'.$OnlinePayment->id;
    //             $amount = $billAmount;

    //             $successUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/precharge/'.$uniqueId.'/?payment_id='.$OnlinePayment->id);
    //             $cancelUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId);
    //             $errorUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId);
    //             // $callbackUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId);

    //             $MyTillpayments = new \Wisdom\MyTillpayments($config);
    //             $response = $MyTillpayments->preauthorize($txnId, $amount, [
    //                 'successUrl' => $successUrl,
    //                 'cancelUrl' => $cancelUrl,
    //                 'errorUrl' => $errorUrl,
    //                 'customer' => [
    //                     'firstName' => $OnlinePayment->first_name,
    //                     'lastName' => $OnlinePayment->last_name,
    //                     'email' => $OnlinePayment->email,
    //                     'billingPhone' => $OnlinePayment->mobile,
    //                 ],
    //             ]);
    //             if (!$response['success']) {
    //                 $throwMessage = 'Till error: ';
    //                 $temp_ = [];
    //                 foreach ($response['errors'] as $key => $error) {
    //                     $temp_[] = $error['adapterMessage'];
    //                 }
    //                 $throwMessage .= implode(', ', $temp_);
    //                 throw new Exception($throwMessage, 500);
    //                 return;
    //             }
    //             $OnlinePayment->tillpayments_preauthorize_merchant_transaction_id = $response['merchantTransactionId'];
    //             $OnlinePayment->tillpayments_preauthorize_datetime = $response['datetime'];
    //             $OnlinePayment->tillpayments_preauthorize_signature = $response['signature'];
    //             $OnlinePayment->tillpayments_preauthorize_success = $response['success'];
    //             $OnlinePayment->tillpayments_preauthorize_uuid = $response['uuid'];
    //             $OnlinePayment->tillpayments_preauthorize_purchase_id = $response['purchaseId'];
    //             $OnlinePayment->tillpayments_preauthorize_redirect_url = $response['redirectUrl'];
    //             $OnlinePayment->tillpayments_preauthorize_response = json_encode($response);
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $this->db->commit();
    //             return $this->response->redirect($response['redirectUrl'], true);
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             $this->flashSession->error($e->getMessage());
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //         }
    //     } else if ($paymentMethod==='OFFLINE' && $this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT'])  {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/precharge/'.$uniqueId), true);
    //     } else {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'tableorderSession' => $tableorderSession,
    //         'billAmount' => $billAmount,
    //     ]);
    //     $viewpath = 'tableorder/prepayment/'.strtolower($paymentMethod);
    //     if (strtoupper($paymentMethod)==='EWAY') {
    //         $this->assets->addJs('https://secure.ewaypayments.com/scripts/eCrypt.min.js', false, false);
    //     }
    //     $this->assets->addJs('assets/'.$viewpath.'.js');
    //     $this->view->pick($viewpath);
    // }

    // public function prechargeAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $cart_ = $this->session->get('tableorder-cart_', []);
    //     $ticketId = $this->ticketId;
    //     $TableorderSession = $this->TableorderSession;

    //     if (empty($TableorderSession->payment_method) && empty($TableorderSession->first_name)) {
    //         $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     if ($TableorderSession->payment_method!=='OFFLINE' && empty($TableorderSession->mobile)) {
    //         $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //     }

    //     if ($TableorderSession->payment_method==='FISERV') {
    //         $cart_ = json_decode($TableorderSession->temp ?: '[]', true);
    //         $this->session->set('tableorder-cart_', $cart_);
    //     }

    //     $tableorderSession = TableorderSession::prepareArray($TableorderSession);
    //     $paymentMethod = $TableorderSession->payment_method;

    //     $subtotal =  $this->refreshCart($cart_);
    //     $discountTotal = 0;
    //     $total = $subtotal-$discountTotal;
    //     $deliveryCharge = 0;
    //     $additionalSurcharge = 0;

    //     $serviceChargePercentage = 0;
    //     if (
    //       ($service['name']==='DELIVERY' && intval($this->cfg['SERVICE_CHARGE_DELIVERY']??1)>0) ||
    //       ($service['name']==='DINE IN' && intval($this->cfg['SERVICE_CHARGE_DINE_IN']??1)>0) ||
    //       ($service['name']==='TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - DELIVERY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_DELIVERY']??1)>0) ||
    //       ($service['name']==='ONLINE ORDER - TAKE AWAY' && intval($this->cfg['SERVICE_CHARGE_ONLINE_ORDER_TAKE_AWAY']??1)>0) ||
    //       ($service['name']==='TABLE ORDER' && intval($this->cfg['SERVICE_CHARGE_TABLE_ORDER']??1)>0) ||
    //       ($service['name']==='GENERAL' && intval($this->cfg['SERVICE_CHARGE_GENERAL']??1)>0)
    //     ) {
    //       $serviceChargePercentage = $this->cfg['SERVICE_CHARGE'] ?? 0;
    //     }

    //     $serviceCharge = 0;
    //     if ($serviceChargePercentage>0) {
    //         $serviceCharge = $serviceChargePercentage*$total;
    //     }
    //     $ppn = 0;
    //     if (($this->cfg['TAX_CALCULATION']??'GST')==='PPN') {
    //         $ppn = $this->cfg['PPN']*($total+$serviceCharge);
    //     }
    //     $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn;

    //     $rounding = 0;
    //     $billAmount = $grandTotal;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $billAmount;
    //         $billAmount = $this->tool->rounding($billAmount);
    //         $rounding = $billAmount-$beforeRounding;
    //         $grandTotal = $total+$deliveryCharge+$additionalSurcharge+$serviceCharge+$ppn+$rounding;
    //     }
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0. dari precharge');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }

    //     // PINPAYMENTS
    //     $cardToken = $this->request->getPost('card_token', 'string', null);
    //     $cardType = $this->request->getPost('card_type', 'string', null);
    //     // EWAY
    //     $cardHolderName = $this->request->getPost('card_holder_name', 'string', null);
    //     $cardNumber = $this->request->getPost('card_number', 'string', null);
    //     $cardCcv = $this->request->getPost('card_ccv', 'string', null);
    //     $cardExpiryMonth = $this->request->getPost('card_expiry_month', 'string', null);
    //     $cardExpiryYear = $this->request->getPost('card_expiry_year', 'string', null);
    //     // XENDIT, TILLPAYMENT & FISERV
    //     $paymentId = $this->request->getQuery('payment_id', 'string', null);
    //     // FISERV
    //     // field2 yg di terangkan di PDF Guide
    //     $approval_code = $this->request->getPost('approval_code', 'string', '');
    //     $oid = $this->request->getPost('oid', 'string', '');
    //     $refnumber = $this->request->getPost('refnumber', 'string', '');
    //     $status = $this->request->getPost('status', 'string', '');
    //     $txndate_processed = $this->request->getPost('txndate_processed', 'string', '');
    //     $ipgTransactionId = $this->request->getPost('ipgTransactionId', 'string', '');
    //     $tdate = $this->request->getPost('tdate', 'string', '');
    //     $fail_reason = $this->request->getPost('fail_reason', 'string', '');
    //     $response_hash = $this->request->getPost('response_hash', 'string', '');
    //     $processor_response_code = $this->request->getPost('processor_response_code', 'string', '');
    //     $terminal_id = $this->request->getPost('terminal_id', 'string', '');
    //     $ccbin = $this->request->getPost('ccbin', 'string', '');
    //     $cccountry = $this->request->getPost('cccountry', 'string', '');
    //     $ccbrand = $this->request->getPost('ccbrand', 'string', '');
    //     $schemeTransactionId = $this->request->getPost('schemeTransactionId', 'string', '');

    //     // field2 yg tidak ada di PDF Guide
    //     $timezone = $this->request->getPost('timezone', 'string', '');
    //     $hash_algorithm = $this->request->getPost('hash_algorithm', 'string', '');
    //     $endpointTransactionId = $this->request->getPost('endpointTransactionId', 'string', '');
    //     $chargetotal = $this->request->getPost('chargetotal', 'string', '');
    //     $response_code_3dsecure = $this->request->getPost('response_code_3dsecure', 'string', '');
    //     $installments_interest = $this->request->getPost('installments_interest', 'string', '');
    //     $paymentMethod = $this->request->getPost('paymentMethod', 'string', '');
    //     $bname = $this->request->getPost('bname', 'string', '');
    //     $cardnumber = $this->request->getPost('cardnumber', 'string', '');
    //     $expmonth = $this->request->getPost('expmonth', 'string', '');
    //     $expyear = $this->request->getPost('expyear', 'string', '');

    //     $fiservResponse = [
    //         'approval_code' => $approval_code,
    //         'oid' => $oid,
    //         'refnumber' => $refnumber,
    //         'status' => $status,
    //         'txndate_processed' => $txndate_processed,
    //         'ipgTransactionId' => $ipgTransactionId,
    //         'tdate' => $tdate,
    //         'fail_reason' => $fail_reason,
    //         'response_hash' => $response_hash,
    //         'processor_response_code' => $processor_response_code,
    //         'terminal_id' => $terminal_id,
    //         'ccbin' => $ccbin,
    //         'cccountry' => $cccountry,
    //         'ccbrand' => $ccbrand,
    //         'schemeTransactionId' => $schemeTransactionId,
    //         'timezone' => $timezone,
    //         'hash_algorithm' => $hash_algorithm,
    //         'endpointTransactionId' => $endpointTransactionId,
    //         'chargetotal' => $chargetotal,
    //         'response_code_3dsecure' => $response_code_3dsecure,
    //         'installments_interest' => $installments_interest,
    //         'paymentMethod' => $paymentMethod,
    //         'bname' => $bname,
    //         'cardnumber' => $cardnumber,
    //         'expmonth' => $expmonth,
    //         'expyear' => $expyear,
    //     ];

    //     if ($TableorderSession->payment_method==='PINPAYMENTS' && $this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //     } else if ($TableorderSession->payment_method==='EWAY' && $this->cfg['TABLE_ORDER_USING_EWAY']) {
    //     } else if ($TableorderSession->payment_method==='XENDIT' && $this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //     } else if ($TableorderSession->payment_method==='FISERV' && $this->cfg['TABLE_ORDER_USING_FISERV']) {
    //     } else if ($TableorderSession->payment_method==='TILLPAYMENTS' && $this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //     } else if ($TableorderSession->payment_method==='OFFLINE' && $this->cfg['TABLE_ORDER_USING_OFFLINE_PAYMENT']) {
    //     } else {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $UserCron = $this->getUserCron();
    //     $DeviceCron = $this->getDeviceCron();

    //     if ($TableorderSession->payment_method!=='OFFLINE') {
    //         $PaymentType = PaymentType::findFirst([
    //             'conditions' => 'fk_branch = {fk_branch} AND name = {name}',
    //             'bind' => [
    //                 'fk_branch' => $this->Branch->id,
    //                 'name' => $TableorderSession->payment_method,
    //             ],
    //         ]);
    //         if (!$PaymentType) {
    //             $this->flashSession->error('Payment type '.$TableorderSession->payment_method.' not found.');
    //             return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //         }
    //     }

    //     $this->db->begin();
    //     try {
    //         $Ticket = $this->proceedCart();

    //         // DEBET PROCESS
    //         if ($TableorderSession->payment_method==='PINPAYMENTS') {
    //             // PINPAYMENTS

    //             $endpoint = $this->config->status==='development' ? 'https://test-api.pinpayments.com' : 'https://api.pinpayments.com';
    //             $public = $this->config->status==='development' ? $this->cfg['PINPAYMENTS_API_PUBLIC_SANDBOX'] : $this->cfg['PINPAYMENTS_API_PUBLIC'];
    //             $secret = $this->config->status==='development' ? $this->cfg['PINPAYMENTS_API_SECRET_SANDBOX'] : $this->cfg['PINPAYMENTS_API_SECRET'];
    //             $APIConfig = (object) [
    //                 'EndPoint' => $endpoint,
    //                 'PublicKey' => $public,
    //                 'SecretKey' => $secret,
    //             ];

    //             try {
    //                 $MyPinpayments = new \Wisdom\MyPinpayments($APIConfig);
    //             } catch(Exception $e) {
    //                 $this->db->rollback();
    //                 $this->flashSession->error("Can't connect to pinpayments server");
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    //             }

    //             if (!($this->cfg['PINPAYMENTS_USING_PAYMENT_PAGE']??0)) {
    //                 // DIRECT CHARGE

    //                 if (!$cardToken || !$cardType) {
    //                     throw new Exception("Bad request", 400);
    //                 }
    
    //                 $surcharge = 0;
    //                 if ($this->cfg['PINPAYMENTS_HAS_SURCHARGE']) {
    //                     $formula = $this->cfg['PINPAYMENTS_SURCHARGE_DEFAULT_FORMULA'] ?? '';
    //                     $formulaAmex = $this->cfg['PINPAYMENTS_SURCHARGE_AMEX_FORMULA'] ?? '';
    
    //                     if ($cardType==='AMEX') {
    //                         $surcharge = \Wisdom\Tool::formulaReader($formulaAmex, $billAmount)  ?? 0;
    //                     } else {
    //                         $surcharge = \Wisdom\Tool::formulaReader($formula, $billAmount) ?? 0;
    //                     }
    //                     $surcharge = floatval(number_format($surcharge, 2));
    //                     $billAmount += $surcharge;
    //                 }
                    
    //                 $response = $MyPinpayments->charge(
    //                     $TableorderSession->email,
    //                     '#'.$Ticket->barcode.' ('.$Ticket->id.')',
    //                     $billAmount,
    //                     $_SERVER['HTTP_CLIENT_IP']??$_SERVER['HTTP_X_FORWARDED_FOR']??$_SERVER['REMOTE_ADDR'],
    //                     $cardToken
    //                 ); // debet
    
    //                 if (!$response->success) {
    //                     $this->db->rollback();
    //                     $this->flashSession->error($response->error_message);
    //                     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    //                 }
    
    //                 if ($this->cfg['PINPAYMENTS_HAS_SURCHARGE']) {
    //                     $Ticket->additional_surcharge += $surcharge;
    //                     $Ticket->grand_total += $surcharge;
    //                     if (!$Ticket->save()) {
    //                         throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //                     }
    //                 }
    //             } else {
    //                 // PINPAYMENTS 2
    //                 // PAYMENT PAGE
    //                 // belum ada aba-aba untuk diterapkan
    //                 $this->flashSession->error('Fatal error: Invalid payment page configuration.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='EWAY') {
    //             if (!$cardHolderName || !$cardNumber || !$cardCcv || !$cardExpiryMonth || !$cardExpiryYear) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $valid = true;
    //             if (!preg_match('/^eCrypted\:/', $cardNumber)) {
    //                 $this->flashSession->error('Card Number is not encrypted');
    //                 $valid = false;
    //             }
    //             if (!preg_match('/^eCrypted\:/', $cardCcv)) {
    //                 $this->flashSession->error('Card CCV is not encrypted');
    //                 $valid = false;
    //             }

    //             if (!$valid) {
    //                 $this->db->rollback();
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    //             }

    //             $MyEway = new \Wisdom\MyEway(
    //                 $this->config->status==='development' ? $this->cfg['EWAY_API_KEY_SANDBOX'] : $this->cfg['EWAY_API_KEY'],
    //                 $this->config->status==='development' ? $this->cfg['EWAY_API_PASSWORD_SANDBOX'] : $this->cfg['EWAY_API_PASSWORD'],
    //                 $this->config->status==='development' ? \Eway\Rapid\Client::MODE_SANDBOX : \Eway\Rapid\Client::MODE_PRODUCTION
    //             );

    //             $MyEway->setCustomer(
    //                 null,
    //                 null,
    //                 null,
    //                 $TableorderSession->first_name,
    //                 $TableorderSession->last_name,
    //                 null,
    //                 null,
    //                 null, // address
    //                 null,
    //                 null, // suburb
    //                 null, // state
    //                 null, // postcode
    //                 'AU',
    //                 $TableorderSession->email,
    //                 null,
    //                 $TableorderSession->mobile
    //             );

    //             $MyEway->setCardDetail(
    //                 $cardHolderName,
    //                 $cardNumber,
    //                 $cardExpiryMonth,
    //                 $cardExpiryYear,
    //                 $cardCcv
    //             );

    //             $MyEway->setPayment(
    //                 round($billAmount, 2),
    //                 $Ticket->barcode,
    //                 'Table Order Grand Total: $'.$billAmount,
    //                 'Table Order #'.$Ticket->barcode.' ('.$Ticket->id.')',
    //                 'AUD'
    //             );

    //             // debet
    //             $EwayResponse = $MyEway->directPurchase();

    //             if (!$EwayResponse->TransactionStatus) {
    //                 $this->db->rollback();
    //                 $errorCode_   = explode(',', $EwayResponse->Errors);
    //                 foreach ($errorCode_ as $key => $errorCode) {
    //                     $errorMessage = \Eway\Rapid::getMessage($errorCode);
    //                     if(empty($errorMessage)) {
    //                         $this->flashSession->error('Unknown Eway error, code: '.$errorCode);
    //                     } else {
    //                         $this->flashSession->error($errorMessage);
    //                     }
    //                 }
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='XENDIT') {
    //             // XENDIT
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'method' => 'XENDIT',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->xendit_invoice_status==='PAID') {
    //                 $this->flashSession->notice('This invoice for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }

    //             $APIConfig = $this->config->api->{$this->config->status}->XENDIT;
    //             Xendit::setApiKey($APIConfig['SecretKey']);

    //             $params = [
    //                 'for-user-id' => $this->cfg['XENDIT_ACCOUNT_ID'],
    //             ];
    //             $getInvoice = \Xendit\Invoice::retrieve($OnlinePayment->xendit_invoice_id, $params);

    //             $getInvoiceJson = json_encode($getInvoice);

    //             if ($getInvoice['status']!=='PAID') {
    //                 $this->flashSession->error('Xendit failed! This Invoice is not paid yet');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId), true);
    //             }

    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->xendit_invoice_status = $getInvoice['status'];
    //             $OnlinePayment->xendit_get_invoice_json = $getInvoiceJson;
    //             // savenya di bawah
    //         } elseif ($TableorderSession->payment_method==='FISERV') {
    //             // FISERV
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if (empty($approval_code) || empty($status) || empty($response_hash)) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $TableorderSession->temp = null;
    //             if (!$TableorderSession->save()) {
    //                 throw new Exception(implode(', ', $TableorderSession->getMessages()), 500);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'method' => 'FISERV',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->fiserv_status==='APPROVED') {
    //                 $this->flashSession->notice('This payment for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }
    //             if ($status!=='APPROVED') {
    //                 if ($status==='DECLINED') {
    //                     $this->flashSession->warning('Your card is declined');
    //                 } else {
    //                     $this->flashSession->warning('Your payment is failed');
    //                 }
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId, [
    //                     'method' => 'fiserv',
    //                 ]), true);
    //             }

    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->fiserv_status = $status;
    //             $OnlinePayment->fiserv_approval_code = $approval_code;
    //             $OnlinePayment->fiserv_response_hash = $response_hash;
    //             $OnlinePayment->fiserv_refnumber = $refnumber;
    //             $OnlinePayment->fiserv_ipg_transaction_id = $ipgTransactionId;
    //             $OnlinePayment->fiserv_terminal_id = $terminal_id;
    //             $OnlinePayment->fiserv_scheme_transaction_id = $schemeTransactionId;
    //             $OnlinePayment->fiserv_response = json_encode($fiservResponse);
    //             // savenya di bawah

    //             $debetAmount = number_format($OnlinePayment->total, 2);
    //             $APIConfig = $this->getFiservConfig();
    //             $MyFiserv = new \Wisdom\MyFiserv($APIConfig, $this->config->timezone);
    //             $hash = $MyFiserv->createResponseHash($approval_code, $debetAmount, $OnlinePayment->created);
    //             if ($response_hash!==$hash) {
    //                 $this->flashSession->warning('Payment attempt failed');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/prepayment/'.$uniqueId, [
    //                     'method' => 'fiserv',
    //                 ]), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='TILLPAYMENTS') {
    //             // TILLPAYMENTS
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'method' => 'TILLPAYMENTS',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->tillpayments_capture_success==1) {
    //                 $this->flashSession->notice('This invoice for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }

    //             $APIConfig = $this->config->api->{$this->config->status}->TILLPAYMENTS;

    //             $config = (object) [
    //                 'apiKey' => $this->cfg['TILLPAYMENTS_API_KEY'],
    //                 'apiSharedSecret' => $this->cfg['TILLPAYMENTS_API_SHARED_SECRET'],
        
    //                 'apiUsername' =>  $this->cfg['TILLPAYMENTS_API_USERNAME'],
    //                 'apiPassword' =>  $this->cfg['TILLPAYMENTS_API_PASSWORD'],
    //                 'publicIntegrationKey' => $this->cfg['TILLPAYMENTS_API_PUBLIC_INTEGRATION_KEY'],
        
    //                 'endpoint' => $APIConfig->endpoint,
    //             ];

    //             $txnId = 't'.$Ticket->id;
    //             $amount = $billAmount;
    //             $referenceUuid = $OnlinePayment->tillpayments_preauthorize_uuid;

    //             $MyTillpayments = new \Wisdom\MyTillpayments($config);
    //             $response = $MyTillpayments->capture($referenceUuid, $txnId, $amount);
    //             if (!$response['success']) {
    //                 $throwMessage = 'Till error: ';
    //                 $temp_ = [];
    //                 foreach ($response['errors'] as $key => $error) {
    //                     $temp_[] = $error['adapterMessage'];
    //                 }
    //                 $throwMessage .= implode(', ', $temp_);
    //                 throw new Exception($throwMessage, 500);
    //                 return;
    //             }
    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->tillpayments_capture_merchant_transaction_id = $response['merchantTransactionId'];
    //             $OnlinePayment->tillpayments_capture_datetime = $response['datetime'];
    //             $OnlinePayment->tillpayments_capture_signature = $response['signature'];
    //             $OnlinePayment->tillpayments_capture_success = $response['success'];
    //             $OnlinePayment->tillpayments_capture_uuid = $response['uuid'];
    //             $OnlinePayment->tillpayments_capture_purchase_id = $response['purchaseId'];
    //             $OnlinePayment->tillpayments_capture_response = json_encode($response);
    //             // savenya di bawah
    //         } elseif ($TableorderSession->payment_method==='OFFLINE') {
    //         } else {
    //             throw new Exception("Online Payment Method is undefined", 500);
    //         }

    //         if(($this->cfg['USE_ROUNDING']??0)) {
    //             $Ticket->rounding = $rounding;
    //             $Ticket->grand_total = $grandTotal;
    //         }

    //         if ($TableorderSession->payment_method!=='OFFLINE') {

    //             $TicketPayment = new TicketPayment();
    //             $TicketPayment->fk_ticket = $Ticket->id;
    //             $TicketPayment->fk_user = $UserCron->id;
    //             $TicketPayment->fk_device = $DeviceCron->id;
    //             $TicketPayment->fk_payment_type = $PaymentType->id;
    //             $TicketPayment->fk_user_update = null;
    //             $TicketPayment->fk_void = null;
    //             $TicketPayment->payment_type_name = $PaymentType->name;
    //             $TicketPayment->payment_type_special = $PaymentType->special;
    //             $TicketPayment->total = $billAmount;
    //             $TicketPayment->change = 0;
    //             $TicketPayment->note = null;
    //             $TicketPayment->information = null;
    //             $TicketPayment->created = date('Y-m-d H:i:s');
    //             $TicketPayment->updated = date('Y-m-d H:i:s');
    //             if (!$TicketPayment->create()) {
    //                 throw new Exception(implode(', ', $TicketPayment->getMessages()), 500);
    //             }

    //             $Ticket->payment = $Ticket->payment+$billAmount;
    //             if ($Ticket->payment>=$Ticket->grand_total) {
    //                 $Ticket->paid = date('Y-m-d H:i:s');
    //                 $Customer = null;
    //                 if ($Ticket->fk_customer) {
    //                     $Customer = $Ticket->getCustomer();
    //                 }
    //                 $ticketPayment_ = [];
    //                 $ticketPayment_[0] = [];
    //                 $ticketPayment_[0]['payment_type_name'] = $PaymentType->name;
    //                 $ticketPayment_[0]['amount'] = $billAmount;
    //                 $ticketPayment_[0]['change'] = 0;
    //                 $LoyaltyMovement             = LoyaltyMovement::createFromTicket(
    //                     $Ticket,
    //                     $Customer,
    //                     $ticketPayment_
    //                 );
    //                 if ($LoyaltyMovement) {
    //                     $loyaltyCustomer = Customer::changePoint(
    //                         $Customer,
    //                         $LoyaltyMovement
    //                     );
    //                     if ($loyaltyCustomer) {
    //                         $resultcustomer = Customer::prepareArray($loyaltyCustomer);
    //                     }
    //                 }
    //             }
    //             $Ticket->updated = date('Y-m-d H:i:s');
    //             if (!$Ticket->update()) {
    //                 throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //             }

    //             if (in_array($TableorderSession->payment_method, ['PINPAYMENTS', 'EWAY'])) {
    //                 $OnlinePayment = new OnlinePayment();
    //                 $OnlinePayment->fk_branch = $this->Branch->id;
    //                 $OnlinePayment->fk_indirect_order = null;
    //                 $OnlinePayment->fk_ticket = $Ticket->id;
    //                 $OnlinePayment->fk_ticket_payment = $TicketPayment->id;
    //                 $OnlinePayment->first_name = $TableorderSession->first_name;
    //                 $OnlinePayment->last_name = $TableorderSession->last_name;
    //                 $OnlinePayment->mobile = $TableorderSession->mobile;
    //                 $OnlinePayment->email = $TableorderSession->email;
    //                 $OnlinePayment->total = $billAmount;
    //                 $OnlinePayment->created = date('Y-m-d H:i:s');
    //                 $OnlinePayment->status = 0;
    //                 $OnlinePayment->method = $TableorderSession->payment_method;
    //                 $OnlinePayment->pinpayments_card_token = null;
    //                 $OnlinePayment->pinpayments_response = null;
    //                 $OnlinePayment->pinpayments_token = null;
    //                 $OnlinePayment->eway_access_code = null;
    //                 $OnlinePayment->eway_response = null;
    //                 $OnlinePayment->eway_txn = null;
    //                 $OnlinePayment->xendit_invoice_id = null;
    //                 $OnlinePayment->xendit_invoice_status = null;
    //                 $OnlinePayment->xendit_create_invoice_json = null;
    //                 $OnlinePayment->xendit_get_invoice_json = null;
    //                 if (!$OnlinePayment->create()) {
    //                     throw new Exception(implode(', ', $OnlinePayment->getMessages()), 500);
    //                 }
    //             }

    //             if ($TableorderSession->payment_method==='PINPAYMENTS') {
    //                 $OnlinePayment->status = 1;
    //                 $OnlinePayment->pinpayments_card_token = $cardToken;
    //                 $OnlinePayment->pinpayments_response = json_encode($response);
    //                 $OnlinePayment->pinpayments_token = $response->token;
    //             } elseif ($TableorderSession->payment_method==='EWAY') {
    //                 $OnlinePayment->status = 1;
    //                 $OnlinePayment->eway_access_code = null;
    //                 $OnlinePayment->eway_response = json_encode($EwayResponse);
    //                 $OnlinePayment->eway_txn = $EwayResponse->TransactionID;
    //             } elseif ($TableorderSession->payment_method==='XENDIT') {
    //                 $OnlinePayment->status = 1;
    //             } elseif ($TableorderSession->payment_method==='FISERV') {
    //                 $OnlinePayment->status = 1;
    //             }
    //             if (!$OnlinePayment->update()) {
    //                 throw new Exception(implode(', ', $OnlinePayment->getMessages()), 500);
    //             }
    //         }

    //         $this->db->commit();
    //     } catch(Exception $e) {
    //         $this->db->rollback();
    //         throw $e;
    //     }

    //     if ($TableorderSession->payment_method!=='OFFLINE') {
    //         // Email For Customer
    //         $emailBody = 'Thank you, your payment has been received.'."<br/>";
    //         $emailBody .= 'Table Order #'.$Ticket->barcode.' ('.$Ticket->id.') $'.number_format($billAmount, 2)."<br/>";
    //         $emailBody .= $this->Branch->name.' - '.$this->Branch->phone."<br/>";

    //         if (!empty($this->Branch->email)) {
    //             $Email = new Email();
    //             $Email->fk_user         = null;
    //             $Email->fk_device       = null;
    //             $Email->ref             = 'THANKYOU-'.$Ticket->id;
    //             $Email->from_name       = $this->Branch->name;
    //             $Email->from_address    = $this->Branch->email;
    //             $Email->reply_name      = $this->Branch->name;
    //             $Email->reply_address   = $this->Branch->email;
    //             $Email->to_name         = $TableorderSession->firstname.' '.$TableorderSession->lastname;
    //             $Email->to_address      = $TableorderSession->email;
    //             $Email->subject         = 'Thank you for your order #'.$Ticket->barcode;
    //             $Email->content         = $emailBody;
    //             $Email->priority        = 0;
    //             $Email->created         = date('Y-m-d H:i:s');
    //             if (!$Email->save()) {
    //                 throw new Exception(implode($Email->getMessages()), 500);
    //             }
    //         }
    //     }

    //     $this->session->remove('tableorder-cart_'); // clear cart

    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    // }

    // protected function getTicketArray($ticketId)
    // {
    //     $Ticket = Ticket::findFirst([
    //         'conditions' => 'fk_branch = {fk_branch} AND fk_void IS NULL AND id = {id}',
    //         'bind' => [
    //             'fk_branch' => $this->Branch->id,
    //             'id' => $ticketId,
    //         ],
    //     ]);
    //     if (!$Ticket) {
    //         return false;
    //     }
    //     $ticket = Ticket::prepareArray($Ticket);

    //     $TicketItem_ = $Ticket->getTicketItem([
    //         'conditions' => 'fk_void IS NULL AND fk_ticket_item IS NULL',
    //     ]);
    //     $ticketItem_ = ['ordinal'=>[]];
    //     if ($TicketItem_) {
    //         $ticketItem_ = TicketItem::prepareArray($TicketItem_);
    //         foreach ($TicketItem_ as $key1 => $TicketItem) {
    //             $ticketItem = $ticketItem_[$TicketItem->id];

    //             $ChildItem_ = $Ticket->getTicketItem([
    //                 'conditions' => 'fk_ticket_item = {fk_ticket_item}',
    //                 'bind' => [
    //                     'fk_ticket_item' => $TicketItem->id,
    //                 ],
    //             ]);
    //             $childItem_ = ['ordinal'=>[]];
    //             if ($ChildItem_) {
    //                 $childItem_ = TicketItem::prepareArray($ChildItem_);
    //                 foreach ($ChildItem_ as $key2 => $ChildItem) {
    //                     $childItem = $childItem_[$ChildItem->id];

    //                     $ChildItemChoice_ = $ChildItem->getTicketItemChoice([
    //                         'conditions' => 'fk_void IS NULL',
    //                     ]);
    //                     $childItemChoice_ = ['ordinal'=>[]];
    //                     if ($ChildItemChoice_) {
    //                         $childItemChoice_ = TicketItemChoice::prepareArray($ChildItemChoice_);
    //                     }
    //                     $childItem['childItemChoice_'] = $childItemChoice_;

    //                     $childItem_[$ChildItem->id] = $childItem;
    //                 }
    //             }
    //             $ticketItem['childItem_'] = $childItem_;

    //             $TicketItemChoice_ = $TicketItem->getTicketItemChoice([
    //                 'conditions' => 'fk_void IS NULL',
    //             ]);
    //             $ticketItemChoice_ = ['ordinal'=>[]];
    //             if ($TicketItemChoice_) {
    //                 $ticketItemChoice_ = TicketItemChoice::prepareArray($TicketItemChoice_);
    //             }
    //             $ticketItem['ticketItemChoice_'] = $ticketItemChoice_;

    //             $TicketItemDiscount_ = $TicketItem->getTicketItemDiscount([
    //                 'conditions' => 'fk_void IS NULL',
    //             ]);
    //             $ticketItemDiscount_ = ['ordinal'=>[]];
    //             if ($TicketItemDiscount_) {
    //                 $ticketItemDiscount_ = TicketItemDiscount::prepareArray($TicketItemDiscount_);
    //             }
    //             $ticketItem['ticketItemDiscount_'] = $ticketItemDiscount_;

    //             $ticketItem_[$TicketItem->id] = $ticketItem;
    //         }
    //     }
    //     $ticket['ticketItem_'] = $ticketItem_;

    //     $TicketDiscount_ = $Ticket->getTicketDiscount([
    //         'conditions' => 'fk_void IS NULL',
    //     ]);
    //     $ticketDiscount_ = ['ordinal'=>[]];
    //     if ($TicketDiscount_) {
    //         $ticketDiscount_ = TicketDiscount::prepareArray($TicketDiscount_);
    //     }
    //     $ticket['ticketDiscount_'] = $ticketDiscount_;

    //     $TicketPayment_ = $Ticket->getTicketPayment([
    //         'conditions' => 'fk_void IS NULL',
    //     ]);
    //     $ticketPayment_ = ['ordinal'=>[]];
    //     if ($TicketPayment_) {
    //         $ticketPayment_ = TicketPayment::prepareArray($TicketPayment_);
    //     }
    //     $ticket['ticketPayment_'] = $ticketPayment_;

    //     return $ticket;
    // }

    // public function billAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $ticketId = $this->ticketId;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('Please purchase first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $ticket = $this->getTicketArray($ticketId);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     foreach ($ticket['ticketItem_']['ordinal'] as $ticket_) {
    //         $fkMenu = $ticket['ticketItem_'][$ticket_]['fk_menu'];
    //         $menu = Menu::prepareArray($this->getMenu($fkMenu, $this->Branch->id));
    //         $ticket['ticketItem_'][$ticket_]['spicy'] = $menu['spicy'];
    //         $ticket['ticketItem_'][$ticket_]['chef'] = $menu['chef'];
    //         $ticket['ticketItem_'][$ticket_]['popular'] = $menu['popular'];
    //     };

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'service' => $service,
    //         'ticket' => $ticket,
    //     ]);
    // }

    // public function paymentMethodAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $ticketId = $this->ticketId;
    //     $tableorderSession = TableorderSession::prepareArray($this->TableorderSession);

    //     if (!$ticketId) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $Ticket = $this->getTicket($ticketId, $this->Branch->id);
    //     $ticket = Ticket::prepareArray($Ticket);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $billAmount = $Ticket->grand_total-$Ticket->payment;
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }

    //     if (!$this->cfg['TABLE_ORDER_USING_PINPAYMENTS'] &&
    //     !$this->cfg['TABLE_ORDER_USING_EWAY'] &&
    //     !$this->cfg['TABLE_ORDER_USING_XENDIT'] &&
    //     !$this->cfg['TABLE_ORDER_USING_FISERV'] &&
    //     !$this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //         $this->flashSession->notice('We are sorry, It seems there is no payment method was set up.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }

    //     $paymentMethod_ = [];
    //     if ($this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //         $paymentMethod_[] = [
    //             'code' => 'pinpayments',
    //             'label' => 'Credit Card via Pinpayments',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_EWAY']) {
    //         $paymentMethod_[] = [
    //             'code' => 'eway',
    //             'label' => 'Credit Card via Eway',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //         $paymentMethod_[] = [
    //             'code' => 'xendit',
    //             'label' => 'QRIS/Credit card/Debit',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_FISERV']) {
    //         $paymentMethod_[] = [
    //             'code' => 'fiserv',
    //             'label' => 'Credit Card via Fiserv',
    //         ];
    //     }
    //     if ($this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //         $paymentMethod_[] = [
    //             'code' => 'tillpayments',
    //             'label' => 'Credit Card via Tillpayments',
    //         ];
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'ticket' => $ticket,
    //         'tableorderSession' => $tableorderSession,
    //         'paymentMethod_' => $paymentMethod_,
    //     ]);
    // }

    // public function paymentMethodSubmitAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $ticketId = $this->ticketId;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $Ticket = $this->getTicket($ticketId, $this->Branch->id);
    //     $ticket = Ticket::prepareArray($Ticket);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $grandTotal = $Ticket->grand_total;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $grandTotal;
    //         $grandTotal = $this->tool->rounding($grandTotal);
    //         $rounding = $grandTotal-$beforeRounding;
    //     }
    //     $billAmount = $grandTotal-$Ticket->payment;
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }

    //     $firstname = $this->request->getPost('firstname', 'string', '');
    //     $lastname = $this->request->getPost('lastname', 'string', '');
    //     $mobile = $this->request->getPost('mobile', 'string', '');
    //     $email = $this->request->getPost('email', 'email', '');
    //     $method = $this->request->getPost('method', 'string', '');

    //     $post = [
    //         'firstname' => $firstname,
    //         'lastname' => $lastname,
    //         'mobile' => $mobile,
    //         'email' => $email,
    //         'method' => $method,
    //     ];

    //     $Validation = new Validation();
    //     $Validation->add([
    //         'firstname',
    //     ], new VStringLength([
    //         'min' => [
    //             'firstname' => 1,
    //         ],
    //         'max' => [
    //             'firstname' => 100,
    //         ],
    //     ]));
    //     $Validation->add([
    //         'lastname',
    //     ], new VStringLength([
    //         'min' => [
    //             'lastname' => 1,
    //         ],
    //         'max' => [
    //             'lastname' => 100,
    //         ],
    //         'allowEmpty' => true,
    //     ]));
    //     $Validation->add([
    //         'mobile',
    //     ], new VRegex([
    //         'pattern' => [
    //             'mobile' => $this->config->regex->mobile->{$this->Branch->country},
    //         ],
    //     ]));
    //     $Validation->add([
    //         'email',
    //     ], new VEmail([
    //         'allowEmpty' => true,
    //     ]));
    //     $Validation->add([
    //         'method',
    //     ], new VInclusionIn([
    //         'domain' => [
    //             'method' => ['pinpayments', 'eway', 'xendit', 'fiserv', 'tillpayments'],
    //         ],
    //     ]));
    //     try {
    //         $Error_ = $Validation->validate($post);
    //     } catch(VException $e) {
    //         $this->flashSession->error($e->getMessage());
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //     }
    //     if (count($Error_)) {
    //         foreach ($Error_ as $key => $Error) {
    //             $this->flashSession->warning($Error->getMessage());
    //         }
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //     }

    //     $TableorderSession = $this->TableorderSession;
    //     $TableorderSession->first_name = $firstname;
    //     $TableorderSession->last_name = $lastname;
    //     $TableorderSession->mobile = $mobile;
    //     $TableorderSession->email = $email;
    //     $TableorderSession->payment_method = strtoupper($method);
    //     if (!$TableorderSession->save()) {
    //         throw new Exception(implode(',', $TableorderSession->getMessages()), 500);
    //     }
    //     // Tool::predump(TableorderSession::prepareArray($TableorderSession));
    //     // exit;
    //     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //         'method' => $method,
    //     ]), true);
    // }

    // protected function getFiservConfig()
    // {
    //     $APIConfig = (object) [
    //         'StoreId' => ($this->config->status==='development' ? $this->cfg['FISERV_STORE_ID_SANDBOX'] : $this->cfg['FISERV_STORE_ID']),
    //         'SharedSecret' => ($this->config->status==='development' ? $this->cfg['FISERV_SHARED_SECRET_SANDBOX'] : $this->cfg['FISERV_SHARED_SECRET']),
    //         'ConnectURL' => ($this->config->status==='development' ? $this->cfg['FISERV_CONNECT_URL_SANDBOX'] : $this->cfg['FISERV_CONNECT_URL']),
    //         'Currency' => ($this->config->status==='development' ? $this->cfg['FISERV_CURRENCY_SANDBOX'] : $this->cfg['FISERV_CURRENCY']),
    //     ];
    //     return $APIConfig;
    // }

    // public function paymentAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $ticketId = $this->ticketId;
    //     $TableorderSession = $this->TableorderSession;

    //     if (empty($TableorderSession->first_name) ||
    //         // empty($TableorderSession->last_name) ||
    //         empty($TableorderSession->mobile) ||
    //         // empty($TableorderSession->email) ||
    //         empty($TableorderSession->payment_method)) {
    //             $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //     }

    //     $tableorderSession = TableorderSession::prepareArray($TableorderSession);
    //     $paymentMethod = $TableorderSession->payment_method;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $Ticket = $this->getTicket($ticketId, $this->Branch->id);
    //     $ticket = Ticket::prepareArray($Ticket);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     if (!$paymentMethod || !in_array($paymentMethod, ['PINPAYMENTS', 'EWAY', 'XENDIT', 'FISERV', 'TILLPAYMENTS'])) {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $grandTotal = $Ticket->grand_total;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $grandTotal;
    //         $grandTotal = $this->tool->rounding($grandTotal);
    //         $rounding = $grandTotal-$beforeRounding;
    //     }
    //     $billAmount = $grandTotal-$Ticket->payment;
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }
    //     if ($paymentMethod==='PINPAYMENTS' && $this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //     } else if ($paymentMethod==='EWAY' && $this->cfg['TABLE_ORDER_USING_EWAY']) {
    //     } else if ($paymentMethod==='XENDIT' && $this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //         // XENDIT
    //         $APIConfig = $this->config->api->{$this->config->status}->XENDIT;
    //         Xendit::setApiKey($APIConfig['SecretKey']);

    //         $this->db->begin();
    //         try {
    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->fk_ticket_payment = null;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = 'XENDIT';
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $params = [
    //                 'external_id' => 'Payment #'.$OnlinePayment->id,
    //                 'payer_email' => $TableorderSession->email,
    //                 'description' => 'Ticket #'.$Ticket->barcode,
    //                 'amount' => $billAmount,
    //                 //
    //                 'for-user-id' => $this->cfg['XENDIT_ACCOUNT_ID'],
    //                 'success_redirect_url' => $this->absoluteUrl->get($this->branchSlug.'/tableorder/charge/'.$uniqueId.'/?payment_id='.$OnlinePayment->id),
    //                 'failure_redirect_url' => $this->absoluteUrl->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId),
    //             ];
    //             $createInvoice = \Xendit\Invoice::create($params);

    //             $createInvoiceJson = json_encode($createInvoice);

    //             $OnlinePayment->xendit_invoice_id = $createInvoice['id'];
    //             $OnlinePayment->xendit_invoice_status = $createInvoice['status'];
    //             $OnlinePayment->xendit_create_invoice_json = $createInvoiceJson;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $this->db->commit();
    //             return $this->response->redirect($createInvoice['invoice_url'], true);
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             $this->flashSession->error($e->getMessage());
    //         }
    //     } else if ($paymentMethod==='FISERV' && $this->cfg['TABLE_ORDER_USING_FISERV']) {
    //         // FISERV
    //         $OnlinePayment = OnlinePayment::findFirst([
    //             'conditions' => 'fk_branch={fk_branch} '
    //                 .'AND fk_ticket={fk_ticket} '
    //                 .'AND total={total} '
    //                 .'AND status={status} '
    //                 .'AND method={method} ',
    //             'bind' => [
    //                 'fk_branch' => $this->Branch->id,
    //                 'fk_ticket' => $Ticket->id,
    //                 'total' => $billAmount,
    //                 'status' => 0,
    //                 'method' => 'FISERV',
    //             ],
    //             'order' => 'created DESC',
    //         ]);

    //         $APIConfig = $this->getFiservConfig();

    //         $this->db->begin();
    //         try {
    //             if (!$OnlinePayment) {
    //                 $OnlinePayment = new OnlinePayment();
    //                 $OnlinePayment->fk_branch = $this->Branch->id;
    //                 $OnlinePayment->fk_indirect_order = null;
    //                 $OnlinePayment->fk_ticket = $Ticket->id;
    //                 $OnlinePayment->fk_ticket_payment = null;
    //                 $OnlinePayment->first_name = $TableorderSession->first_name;
    //                 $OnlinePayment->last_name = $TableorderSession->last_name;
    //                 $OnlinePayment->mobile = $TableorderSession->mobile;
    //                 $OnlinePayment->email = $TableorderSession->email;
    //                 $OnlinePayment->total = $billAmount;
    //                 $OnlinePayment->created = date('Y-m-d H:i:s');
    //                 $OnlinePayment->status = 0;
    //                 $OnlinePayment->method = 'FISERV';
    //                 $OnlinePayment->pinpayments_card_token = null;
    //                 $OnlinePayment->pinpayments_response = null;
    //                 $OnlinePayment->pinpayments_token = null;
    //                 $OnlinePayment->eway_access_code = null;
    //                 $OnlinePayment->eway_response = null;
    //                 $OnlinePayment->eway_txn = null;
    //                 $OnlinePayment->xendit_invoice_id = null;
    //                 $OnlinePayment->xendit_invoice_status = null;
    //                 $OnlinePayment->xendit_create_invoice_json = null;
    //                 $OnlinePayment->xendit_get_invoice_json = null;
    //                 $OnlinePayment->fiserv_status = null;
    //                 $OnlinePayment->fiserv_approval_code = null;
    //                 $OnlinePayment->fiserv_response_hash = null;
    //                 $OnlinePayment->fiserv_refnumber = null;
    //                 $OnlinePayment->fiserv_ipg_transaction_id = null;
    //                 $OnlinePayment->fiserv_terminal_id = null;
    //                 $OnlinePayment->fiserv_scheme_transaction_id = null;
    //                 $OnlinePayment->fiserv_response = null;
    //                 if (!$OnlinePayment->save()) {
    //                     throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //                 }
    //             }
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->fiserv_status = null;
    //             $OnlinePayment->fiserv_approval_code = null;
    //             $OnlinePayment->fiserv_response_hash = null;
    //             $OnlinePayment->fiserv_refnumber = null;
    //             $OnlinePayment->fiserv_ipg_transaction_id = null;
    //             $OnlinePayment->fiserv_terminal_id = null;
    //             $OnlinePayment->fiserv_scheme_transaction_id = null;
    //             $OnlinePayment->fiserv_response = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $MyFiserv = new \Wisdom\MyFiserv($APIConfig, $this->config->timezone);

    //             $fiserv = $MyFiserv->prepare(
    //                 $OnlinePayment->total,
    //                 'Ticket#'.$Ticket->id.'@'.strtotime($OnlinePayment->created),
    //                 $OnlinePayment->created,
    //                 $this->absoluteUrl->get($this->branchSlug.'/tableorder/charge/'.$uniqueId.'?payment_id='.$OnlinePayment->id),
    //                 $this->absoluteUrl->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId.'?fiserv=fail')
    //             );

    //             $this->db->commit();
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             // $this->flashSession->error($e->getMessage());
    //             Tool::predump($e);
    //             exit;
    //         }

    //         $this->view->setVars([
    //             'fiserv' => $fiserv,
    //         ]);
    //     } else if ($paymentMethod==='TILLPAYMENTS' && $this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //         // TILLPAYMENTS
    //         $APIConfig = $this->config->api->{$this->config->status}->TILLPAYMENTS;

    //         $config = (object) [
    //             'apiKey' => $this->cfg['TILLPAYMENTS_API_KEY'],
    //             'apiSharedSecret' => $this->cfg['TILLPAYMENTS_API_SHARED_SECRET'],
    
    //             'apiUsername' =>  $this->cfg['TILLPAYMENTS_API_USERNAME'],
    //             'apiPassword' =>  $this->cfg['TILLPAYMENTS_API_PASSWORD'],
    //             'publicIntegrationKey' => $this->cfg['TILLPAYMENTS_API_PUBLIC_INTEGRATION_KEY'],
    
    //             'endpoint' => $APIConfig->endpoint,
    //         ];

    //         $this->db->begin();
    //         try {
    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->fk_ticket_payment = null;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = 'TILLPAYMENTS';
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             $OnlinePayment->fiserv_status = null;
    //             $OnlinePayment->fiserv_approval_code = null;
    //             $OnlinePayment->fiserv_response_hash = null;
    //             $OnlinePayment->fiserv_refnumber = null;
    //             $OnlinePayment->fiserv_ipg_transaction_id = null;
    //             $OnlinePayment->fiserv_terminal_id = null;
    //             $OnlinePayment->fiserv_scheme_transaction_id = null;
    //             $OnlinePayment->fiserv_response = null;
    //             $OnlinePayment->tillpayments_preauthorize_merchant_transaction_id = null;
    //             $OnlinePayment->tillpayments_preauthorize_datetime = null;
    //             $OnlinePayment->tillpayments_preauthorize_signature = null;
    //             $OnlinePayment->tillpayments_preauthorize_success = null;
    //             $OnlinePayment->tillpayments_preauthorize_uuid = null;
    //             $OnlinePayment->tillpayments_preauthorize_purchase_id = null;
    //             $OnlinePayment->tillpayments_preauthorize_redirect_url = null;
    //             $OnlinePayment->tillpayments_preauthorize_response = null;
    //             $OnlinePayment->tillpayments_capture_merchant_transaction_id = null;
    //             $OnlinePayment->tillpayments_capture_datetime = null;
    //             $OnlinePayment->tillpayments_capture_signature = null;
    //             $OnlinePayment->tillpayments_capture_success = null;
    //             $OnlinePayment->tillpayments_capture_uuid = null;
    //             $OnlinePayment->tillpayments_capture_purchase_id = null;
    //             $OnlinePayment->tillpayments_capture_response = null;
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $txnId = 't'.$Ticket->id;
    //             $amount = $billAmount;

    //             $successUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/charge/'.$uniqueId.'/?payment_id='.$OnlinePayment->id);
    //             $cancelUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId);
    //             $errorUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId);
    //             // $callbackUrl = $this->absoluteUrl->get($this->branchSlug.'/tableorder/prepayment-method/'.$uniqueId);

    //             $MyTillpayments = new \Wisdom\MyTillpayments($config);
    //             $response = $MyTillpayments->preauthorize($txnId, $amount, [
    //                 'successUrl' => $successUrl,
    //                 'cancelUrl' => $cancelUrl,
    //                 'errorUrl' => $errorUrl,
    //                 'customer' => [
    //                     'firstName' => $OnlinePayment->first_name,
    //                     'lastName' => $OnlinePayment->last_name,
    //                     'email' => $OnlinePayment->email,
    //                     'billingPhone' => $OnlinePayment->mobile,
    //                 ],
    //             ]);
    //             if (!$response['success']) {
    //                 $throwMessage = 'Till error: ';
    //                 $temp_ = [];
    //                 foreach ($response['errors'] as $key => $error) {
    //                     $temp_[] = $error['adapterMessage'];
    //                 }
    //                 $throwMessage .= implode(', ', $temp_);
    //                 throw new Exception($throwMessage, 500);
    //                 return;
    //             }
    //             $OnlinePayment->tillpayments_preauthorize_merchant_transaction_id = $response['merchantTransactionId'];
    //             $OnlinePayment->tillpayments_preauthorize_datetime = $response['datetime'];
    //             $OnlinePayment->tillpayments_preauthorize_signature = $response['signature'];
    //             $OnlinePayment->tillpayments_preauthorize_success = $response['success'];
    //             $OnlinePayment->tillpayments_preauthorize_uuid = $response['uuid'];
    //             $OnlinePayment->tillpayments_preauthorize_purchase_id = $response['purchaseId'];
    //             $OnlinePayment->tillpayments_preauthorize_redirect_url = $response['redirectUrl'];
    //             $OnlinePayment->tillpayments_preauthorize_response = json_encode($response);
    //             if (!$OnlinePayment->save()) {
    //                 throw new Exception(implode($OnlinePayment->getMessages()), 500);
    //             }

    //             $this->db->commit();
    //             return $this->response->redirect($response['redirectUrl'], true);
    //         } catch(Exception $e) {
    //             $this->db->rollback();
    //             $this->flashSession->error($e->getMessage());
    //         }
    //     } else {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'ticket' => $ticket,
    //         'tableorderSession' => $tableorderSession,
    //     ]);
    //     $viewpath = 'tableorder/payment/'.strtolower($paymentMethod);
    //     if (strtoupper($paymentMethod)==='EWAY') {
    //         $this->assets->addJs('https://secure.ewaypayments.com/scripts/eCrypt.min.js', false, false);
    //     }
    //     $this->assets->addJs('assets/'.$viewpath.'.js');
    //     $this->view->pick($viewpath);
    // }

    // public function chargeAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $ticketId = $this->ticketId;
    //     $TableorderSession = $this->TableorderSession;

    //     if (empty($TableorderSession->first_name) ||
    //         // empty($TableorderSession->last_name) ||
    //         empty($TableorderSession->mobile) ||
    //         // empty($TableorderSession->email) ||
    //         empty($TableorderSession->payment_method)) {
    //             $this->flashSession->notice('Please fill bill information first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //     }

    //     if (!$ticketId) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $Ticket = $this->getTicket($ticketId, $this->Branch->id);
    //     $ticket = Ticket::prepareArray($Ticket);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $rounding = 0;
    //     $grandTotal = $Ticket->grand_total;
    //     if(($this->cfg['USE_ROUNDING']??0)) {
    //         $beforeRounding = $grandTotal;
    //         $grandTotal = $this->tool->rounding($grandTotal);
    //         $rounding = $grandTotal-$beforeRounding;
    //     }
    //     $billAmount = $grandTotal-$Ticket->payment;
    //     if ($billAmount<=0) {
    //         $this->flashSession->error('This order is can\'t be paid, nominal bill amount is lesser or equal to 0.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }

    //     // PINPAYMENTS
    //     $cardToken = $this->request->getPost('card_token', 'string', null);
    //     $cardType = $this->request->getPost('card_type', 'string', null);
    //     // EWAY
    //     $cardHolderName = $this->request->getPost('card_holder_name', 'string', null);
    //     $cardNumber = $this->request->getPost('card_number', 'string', null);
    //     $cardCcv = $this->request->getPost('card_ccv', 'string', null);
    //     $cardExpiryMonth = $this->request->getPost('card_expiry_month', 'string', null);
    //     $cardExpiryYear = $this->request->getPost('card_expiry_year', 'string', null);
    //     // XENDIT, TILLPAYMENTS & FISERV
    //     $paymentId = $this->request->getQuery('payment_id', 'string', null);
    //     // FISERV
    //     // field2 yg di terangkan di PDF Guide
    //     $approval_code = $this->request->getPost('approval_code', 'string', '');
    //     $oid = $this->request->getPost('oid', 'string', '');
    //     $refnumber = $this->request->getPost('refnumber', 'string', '');
    //     $status = $this->request->getPost('status', 'string', '');
    //     $txndate_processed = $this->request->getPost('txndate_processed', 'string', '');
    //     $ipgTransactionId = $this->request->getPost('ipgTransactionId', 'string', '');
    //     $tdate = $this->request->getPost('tdate', 'string', '');
    //     $fail_reason = $this->request->getPost('fail_reason', 'string', '');
    //     $response_hash = $this->request->getPost('response_hash', 'string', '');
    //     $processor_response_code = $this->request->getPost('processor_response_code', 'string', '');
    //     $terminal_id = $this->request->getPost('terminal_id', 'string', '');
    //     $ccbin = $this->request->getPost('ccbin', 'string', '');
    //     $cccountry = $this->request->getPost('cccountry', 'string', '');
    //     $ccbrand = $this->request->getPost('ccbrand', 'string', '');
    //     $schemeTransactionId = $this->request->getPost('schemeTransactionId', 'string', '');

    //     // field2 yg tidak ada di PDF Guide
    //     $timezone = $this->request->getPost('timezone', 'string', '');
    //     $hash_algorithm = $this->request->getPost('hash_algorithm', 'string', '');
    //     $endpointTransactionId = $this->request->getPost('endpointTransactionId', 'string', '');
    //     $chargetotal = $this->request->getPost('chargetotal', 'string', '');
    //     $response_code_3dsecure = $this->request->getPost('response_code_3dsecure', 'string', '');
    //     $installments_interest = $this->request->getPost('installments_interest', 'string', '');
    //     $paymentMethod = $this->request->getPost('paymentMethod', 'string', '');
    //     $bname = $this->request->getPost('bname', 'string', '');
    //     $cardnumber = $this->request->getPost('cardnumber', 'string', '');
    //     $expmonth = $this->request->getPost('expmonth', 'string', '');
    //     $expyear = $this->request->getPost('expyear', 'string', '');

    //     $fiservResponse = [
    //         'approval_code' => $approval_code,
    //         'oid' => $oid,
    //         'refnumber' => $refnumber,
    //         'status' => $status,
    //         'txndate_processed' => $txndate_processed,
    //         'ipgTransactionId' => $ipgTransactionId,
    //         'tdate' => $tdate,
    //         'fail_reason' => $fail_reason,
    //         'response_hash' => $response_hash,
    //         'processor_response_code' => $processor_response_code,
    //         'terminal_id' => $terminal_id,
    //         'ccbin' => $ccbin,
    //         'cccountry' => $cccountry,
    //         'ccbrand' => $ccbrand,
    //         'schemeTransactionId' => $schemeTransactionId,
    //         'timezone' => $timezone,
    //         'hash_algorithm' => $hash_algorithm,
    //         'endpointTransactionId' => $endpointTransactionId,
    //         'chargetotal' => $chargetotal,
    //         'response_code_3dsecure' => $response_code_3dsecure,
    //         'installments_interest' => $installments_interest,
    //         'paymentMethod' => $paymentMethod,
    //         'bname' => $bname,
    //         'cardnumber' => $cardnumber,
    //         'expmonth' => $expmonth,
    //         'expyear' => $expyear,
    //     ];

    //     if ($TableorderSession->payment_method==='PINPAYMENTS' && $this->cfg['TABLE_ORDER_USING_PINPAYMENTS']) {
    //     } else if ($TableorderSession->payment_method==='EWAY' && $this->cfg['TABLE_ORDER_USING_EWAY']) {
    //     } else if ($TableorderSession->payment_method==='XENDIT' && $this->cfg['TABLE_ORDER_USING_XENDIT']) {
    //     } else if ($TableorderSession->payment_method==='FISERV' && $this->cfg['TABLE_ORDER_USING_FISERV']) {
    //     } else if ($TableorderSession->payment_method==='TILLPAYMENTS' && $this->cfg['TABLE_ORDER_USING_TILLPAYMENTS']) {
    //     } else {
    //         throw new Exception("Bad request", 400);
    //     }

    //     $UserCron = $this->getUserCron();
    //     $DeviceCron = $this->getDeviceCron();

    //     $PaymentType = PaymentType::findFirst([
    //         'conditions' => 'fk_branch = {fk_branch} AND name = {name}',
    //         'bind' => [
    //             'fk_branch' => $this->Branch->id,
    //             'name' => $TableorderSession->payment_method,
    //         ],
    //     ]);
    //     if (!$PaymentType) {
    //         $this->flashSession->error('Payment type '.$TableorderSession->payment_method.' not found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //     }

    //     $this->db->begin();
    //     try {
    //         // DEBET PROCESS
    //         if ($TableorderSession->payment_method==='PINPAYMENTS') {
    //             // PINPAYMENTS

    //             $endpoint = $this->config->status==='development' ? 'https://test-api.pinpayments.com' : 'https://api.pinpayments.com';
    //             $public = $this->config->status==='development' ? $this->cfg['PINPAYMENTS_API_PUBLIC_SANDBOX'] : $this->cfg['PINPAYMENTS_API_PUBLIC'];
    //             $secret = $this->config->status==='development' ? $this->cfg['PINPAYMENTS_API_SECRET_SANDBOX'] : $this->cfg['PINPAYMENTS_API_SECRET'];
    //             $APIConfig = (object) [
    //                 'EndPoint' => $endpoint,
    //                 'PublicKey' => $public,
    //                 'SecretKey' => $secret,
    //             ];

    //             try {
    //                 $MyPinpayments = new \Wisdom\MyPinpayments($APIConfig);
    //             } catch(Exception $e) {
    //                 $this->db->rollback();
    //                 $this->flashSession->error("Can't connect to pinpayments server");
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'pinpayments',
    //                 ]), true);
    //             }

    //             if (!($this->cfg['PINPAYMENTS_USING_PAYMENT_PAGE']??0)) {
    //                 // DIRECT CHARGE

    //                 if (!$cardToken || !$cardType) {
    //                     throw new Exception("Bad request", 400);
    //                 }

    //                 $surcharge = 0;
    //                 if ($this->cfg['PINPAYMENTS_HAS_SURCHARGE']) {
    //                     $formula = $this->cfg['PINPAYMENTS_SURCHARGE_DEFAULT_FORMULA'] ?? '';
    //                     $formulaAmex = $this->cfg['PINPAYMENTS_SURCHARGE_AMEX_FORMULA'] ?? '';

    //                     if ($cardType==='AMEX') {
    //                         $surcharge = \Wisdom\Tool::formulaReader($formulaAmex, $billAmount)  ?? 0;
    //                     } else {
    //                         $surcharge = \Wisdom\Tool::formulaReader($formula, $billAmount) ?? 0;
    //                     }
    //                     $surcharge = floatval(number_format($surcharge, 2));
    //                     $billAmount += $surcharge;
    //                 }

    //                 $response = $MyPinpayments->charge(
    //                     $TableorderSession->email,
    //                     '#'.$Ticket->barcode.' ('.$Ticket->id.')',
    //                     $billAmount,
    //                     $_SERVER['HTTP_CLIENT_IP']??$_SERVER['HTTP_X_FORWARDED_FOR']??$_SERVER['REMOTE_ADDR'],
    //                     $cardToken
    //                 ); // debet

    //                 if (!$response->success) {
    //                     $this->flashSession->error($response->getMessage());
    //                     return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                         'method' => 'pinpayments',
    //                     ]), true);
    //                 }

    //                 if ($this->cfg['PINPAYMENTS_HAS_SURCHARGE']) {
    //                     $Ticket->additional_surcharge += $surcharge;
    //                     $Ticket->grand_total += $surcharge;
    //                     if (!$Ticket->save()) {
    //                         throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //                     }
    //                 }
    //             } else {
    //                 // PINPAYMENTS 2
    //                 // PAYMENT PAGE
    //                 // belum ada aba-aba untuk diterapkan
    //                 $this->flashSession->error('Fatal error: Invalid payment page configuration.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'pinpayments',
    //                 ]), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='EWAY') {
    //             // EWAY
    //             if (!$cardHolderName || !$cardNumber || !$cardCcv || !$cardExpiryMonth || !$cardExpiryYear) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $valid = true;
    //             if (!preg_match('/^eCrypted\:/', $cardNumber)) {
    //                 $this->flashSession->error('Card Number is not encrypted');
    //                 $valid = false;
    //             }
    //             if (!preg_match('/^eCrypted\:/', $cardCcv)) {
    //                 $this->flashSession->error('Card CCV is not encrypted');
    //                 $valid = false;
    //             }

    //             if (!$valid) {
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'eway',
    //                 ]), true);
    //             }

    //             $MyEway = new \Wisdom\MyEway(
    //                 $this->config->status==='development' ? $this->cfg['EWAY_API_KEY_SANDBOX'] : $this->cfg['EWAY_API_KEY'],
    //                 $this->config->status==='development' ? $this->cfg['EWAY_API_PASSWORD_SANDBOX'] : $this->cfg['EWAY_API_PASSWORD'],
    //                 $this->config->status==='development' ? \Eway\Rapid\Client::MODE_SANDBOX : \Eway\Rapid\Client::MODE_PRODUCTION
    //             );

    //             $MyEway->setCustomer(
    //                 null,
    //                 null,
    //                 null,
    //                 $TableorderSession->first_name,
    //                 $TableorderSession->last_name,
    //                 null,
    //                 null,
    //                 null, // address
    //                 null,
    //                 null, // suburb
    //                 null, // state
    //                 null, // postcode
    //                 'AU',
    //                 $TableorderSession->email,
    //                 null,
    //                 $TableorderSession->mobile
    //             );

    //             $MyEway->setCardDetail(
    //                 $cardHolderName,
    //                 $cardNumber,
    //                 $cardExpiryMonth,
    //                 $cardExpiryYear,
    //                 $cardCcv
    //             );

    //             $MyEway->setPayment(
    //                 round($billAmount, 2),
    //                 $Ticket->barcode,
    //                 'Table Order Grand Total: $'.$billAmount,
    //                 'Table Order #'.$Ticket->barcode.' ('.$Ticket->id.')',
    //                 'AUD'
    //             );

    //             // debet
    //             $EwayResponse = $MyEway->directPurchase();

    //             if (!$EwayResponse->TransactionStatus) {
    //                 $errorCode_   = explode(',', $EwayResponse->Errors);
    //                 foreach ($errorCode_ as $key => $errorCode) {
    //                     $errorMessage = \Eway\Rapid::getMessage($errorCode);
    //                     if(empty($errorMessage)) {
    //                         $this->flashSession->error('Unknown Eway error, code: '.$errorCode);
    //                     } else {
    //                         $this->flashSession->error($errorMessage);
    //                     }
    //                 }
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'eway',
    //                 ]), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='XENDIT') {
    //             // XENDIT
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND fk_ticket = {fk_ticket} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'fk_ticket' => $ticketId,
    //                     'method' => 'XENDIT',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->xendit_invoice_status==='PAID') {
    //                 $this->flashSession->notice('This invoice for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }

    //             $APIConfig = $this->config->api->{$this->config->status}->XENDIT;
    //             Xendit::setApiKey($APIConfig['SecretKey']);

    //             $params = [
    //                 'for-user-id' => $this->cfg['XENDIT_ACCOUNT_ID'],
    //             ];
    //             $getInvoice = \Xendit\Invoice::retrieve($OnlinePayment->xendit_invoice_id, $params);

    //             $getInvoiceJson = json_encode($getInvoice);

    //             if ($getInvoice['status']!=='PAID') {
    //                 $this->flashSession->error('Xendit failed! This Invoice is not paid yet');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment-method/'.$uniqueId), true);
    //             }

    //             $OnlinePayment->xendit_invoice_status = $getInvoice['status'];
    //             $OnlinePayment->xendit_get_invoice_json = $getInvoiceJson;
    //             // savenya di bawah
    //         } elseif ($TableorderSession->payment_method==='FISERV') {
    //             // FISERV
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if (empty($approval_code) || empty($status) || empty($response_hash)) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND fk_ticket = {fk_ticket} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'fk_ticket' => $Ticket->id,
    //                     'method' => 'FISERV',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->fiserv_status==='APPROVED') {
    //                 $this->flashSession->notice('This payment for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }
    //             if ($status!=='APPROVED') {
    //                 if ($status==='DECLINED') {
    //                     $this->flashSession->warning('Your card is declined');
    //                 } else {
    //                     $this->flashSession->warning('Your payment is failed');
    //                 }
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'fiserv',
    //                 ]), true);
    //             }

    //             $OnlinePayment->fiserv_status = $status;
    //             $OnlinePayment->fiserv_approval_code = $approval_code;
    //             $OnlinePayment->fiserv_response_hash = $response_hash;
    //             $OnlinePayment->fiserv_refnumber = $refnumber;
    //             $OnlinePayment->fiserv_ipg_transaction_id = $ipgTransactionId;
    //             $OnlinePayment->fiserv_terminal_id = $terminal_id;
    //             $OnlinePayment->fiserv_scheme_transaction_id = $schemeTransactionId;
    //             $OnlinePayment->fiserv_response = json_encode($fiservResponse);
    //             // save di bawah

    //             $debetAmount = number_format($OnlinePayment->total, 2);
    //             $APIConfig = $this->getFiservConfig();
    //             $MyFiserv = new \Wisdom\MyFiserv($APIConfig, $this->config->timezone);
    //             $hash = $MyFiserv->createResponseHash($approval_code, $debetAmount, $OnlinePayment->created);
    //             if ($response_hash!==$hash) {
    //                 $this->flashSession->warning('Payment attempt failed');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/payment/'.$uniqueId, [
    //                     'method' => 'fiserv',
    //                 ]), true);
    //             }
    //         } elseif ($TableorderSession->payment_method==='TILLPAYMENTS') {
    //             // TILLPAYMENTS
    //             if (!$paymentId) {
    //                 throw new Exception("Bad request", 400);
    //             }

    //             $OnlinePayment = OnlinePayment::findFirst([
    //                 'conditions' => 'id = {id} AND fk_branch = {fk_branch} AND fk_ticket = {fk_ticket} AND method = {method}',
    //                 'bind' => [
    //                     'id' => $paymentId,
    //                     'fk_branch' => $this->Branch->id,
    //                     'fk_ticket' => $ticketId,
    //                     'method' => 'TILLPAYMENTS',
    //                 ],
    //             ]);
    //             if (!$OnlinePayment) {
    //                 throw new Exception("Bad request", 400);
    //             }
    //             if ($OnlinePayment->tillpayments_capture_success==1) {
    //                 $this->flashSession->notice('This invoice for '.$OnlinePayment->total.' is already paid.');
    //                 return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //             }

    //             $APIConfig = $this->config->api->{$this->config->status}->TILLPAYMENTS;

    //             $config = (object) [
    //                 'apiKey' => $this->cfg['TILLPAYMENTS_API_KEY'],
    //                 'apiSharedSecret' => $this->cfg['TILLPAYMENTS_API_SHARED_SECRET'],
        
    //                 'apiUsername' =>  $this->cfg['TILLPAYMENTS_API_USERNAME'],
    //                 'apiPassword' =>  $this->cfg['TILLPAYMENTS_API_PASSWORD'],
    //                 'publicIntegrationKey' => $this->cfg['TILLPAYMENTS_API_PUBLIC_INTEGRATION_KEY'],
        
    //                 'endpoint' => $APIConfig->endpoint,
    //             ];

    //             $txnId = 't'.$Ticket->id;
    //             $amount = $billAmount;
    //             $referenceUuid = $OnlinePayment->tillpayments_preauthorize_uuid;

    //             $MyTillpayments = new \Wisdom\MyTillpayments($config);
    //             $response = $MyTillpayments->capture($referenceUuid, $txnId, $amount);
    //             if (!$response['success']) {
    //                 $throwMessage = 'Till error: ';
    //                 $temp_ = [];
    //                 foreach ($response['errors'] as $key => $error) {
    //                     $temp_[] = $error['adapterMessage'];
    //                 }
    //                 $throwMessage .= implode(', ', $temp_);
    //                 throw new Exception($throwMessage, 500);
    //                 return;
    //             }
    //             $OnlinePayment->tillpayments_capture_merchant_transaction_id = $response['merchantTransactionId'];
    //             $OnlinePayment->tillpayments_capture_datetime = $response['datetime'];
    //             $OnlinePayment->tillpayments_capture_signature = $response['signature'];
    //             $OnlinePayment->tillpayments_capture_success = $response['success'];
    //             $OnlinePayment->tillpayments_capture_uuid = $response['uuid'];
    //             $OnlinePayment->tillpayments_capture_purchase_id = $response['purchaseId'];
    //             $OnlinePayment->tillpayments_capture_response = json_encode($response);
    //             // savenya di bawah
    //         } else {
    //             throw new Exception("Online Payment Method is undefined", 500);
    //         }

    //         if(($this->cfg['USE_ROUNDING']??0)) {
    //             $Ticket->rounding = $rounding;
    //             $Ticket->grand_total = $grandTotal;
    //         }

    //         $TicketPayment = new TicketPayment();
    //         $TicketPayment->fk_ticket = $Ticket->id;
    //         $TicketPayment->fk_user = $UserCron->id;
    //         $TicketPayment->fk_device = $DeviceCron->id;
    //         $TicketPayment->fk_payment_type = $PaymentType->id;
    //         $TicketPayment->fk_user_update = null;
    //         $TicketPayment->fk_void = null;
    //         $TicketPayment->payment_type_name = $PaymentType->name;
    //         $TicketPayment->payment_type_special = $PaymentType->special;
    //         $TicketPayment->total = $billAmount;
    //         $TicketPayment->change = 0;
    //         $TicketPayment->note = null;
    //         $TicketPayment->information = null;
    //         $TicketPayment->created = date('Y-m-d H:i:s');
    //         $TicketPayment->updated = date('Y-m-d H:i:s');
    //         if (!$TicketPayment->create()) {
    //             throw new Exception(implode(', ', $TicketPayment->getMessages()), 500);
    //         }

    //         $Ticket->payment = $Ticket->payment+$billAmount;
    //         if ($Ticket->payment>=$Ticket->grand_total) {
    //             $Ticket->paid = date('Y-m-d H:i:s');
    //             $Customer = null;
    //             if ($Ticket->fk_customer) {
    //                 $Customer = $Ticket->getCustomer();
    //             }
    //             $ticketPayment_ = [];
    //             $ticketPayment_[0] = [];
    //             $ticketPayment_[0]['payment_type_name'] = $PaymentType->name;
    //             $ticketPayment_[0]['amount'] = $billAmount;
    //             $ticketPayment_[0]['change'] = 0;
    //             $LoyaltyMovement            = LoyaltyMovement::createFromTicket(
    //                 $Ticket,
    //                 $Customer,
    //                 $ticketPayment_
    //             );
    //             if ($LoyaltyMovement) {
    //                 $loyaltyCustomer = Customer::changePoint(
    //                     $Customer,
    //                     $LoyaltyMovement
    //                 );
    //                 if ($loyaltyCustomer) {
    //                     $resultcustomer = Customer::prepareArray($loyaltyCustomer);
    //                 }
    //             }
    //             $TicketDiscount_ = $Ticket->getTicketDiscount([
    //                 'conditions' => "fk_void IS NULL AND discount_name = 'LOYALTY POINT REDEMPTION'",
    //                 'order'      => "created",
    //             ]);
    //             foreach ($TicketDiscount_ as $TicketDiscount) {
    //                 $LoyaltyMovement = LoyaltyMovement::createFromTicketDiscount(
    //                     $Ticket,
    //                     $TicketDiscount,
    //                     $Customer
    //                 );
    //                 if ($LoyaltyMovement) {
    //                     $loyaltyCustomer = Customer::changePoint(
    //                         $Customer,
    //                         $LoyaltyMovement
    //                     );
    //                     if ($loyaltyCustomer) {
    //                         $resultcustomer = Customer::prepareArray($loyaltyCustomer);
    //                     }
    //                 }
    //             }
    //         }

    //         if(($this->cfg['USE_ROUNDING']??0)) {
    //             $Ticket->rounding = $rounding;
    //             $Ticket->grand_total = $grandTotal;
    //         }
    //         $Ticket->updated = date('Y-m-d H:i:s');
    //         if (!$Ticket->update()) {
    //             throw new Exception(implode(', ', $Ticket->getMessages()), 500);
    //         }

    //         if (in_array($TableorderSession->payment_method, ['PINPAYMENTS', 'EWAY'])) {
    //             $OnlinePayment = new OnlinePayment();
    //             $OnlinePayment->fk_branch = $this->Branch->id;
    //             $OnlinePayment->fk_indirect_order = null;
    //             $OnlinePayment->fk_ticket = $Ticket->id;
    //             $OnlinePayment->fk_ticket_payment = $TicketPayment->id;
    //             $OnlinePayment->first_name = $TableorderSession->first_name;
    //             $OnlinePayment->last_name = $TableorderSession->last_name;
    //             $OnlinePayment->mobile = $TableorderSession->mobile;
    //             $OnlinePayment->email = $TableorderSession->email;
    //             $OnlinePayment->total = $billAmount;
    //             $OnlinePayment->created = date('Y-m-d H:i:s');
    //             $OnlinePayment->status = 0;
    //             $OnlinePayment->method = $TableorderSession->payment_method;
    //             $OnlinePayment->pinpayments_card_token = null;
    //             $OnlinePayment->pinpayments_response = null;
    //             $OnlinePayment->pinpayments_token = null;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = null;
    //             $OnlinePayment->eway_txn = null;
    //             $OnlinePayment->xendit_invoice_id = null;
    //             $OnlinePayment->xendit_invoice_status = null;
    //             $OnlinePayment->xendit_create_invoice_json = null;
    //             $OnlinePayment->xendit_get_invoice_json = null;
    //             if (!$OnlinePayment->create()) {
    //                 throw new Exception(implode(', ', $OnlinePayment->getMessages()), 500);
    //             }
    //         }
    //         $OnlinePayment->fk_ticket_payment = $TicketPayment->id;

    //         if ($TableorderSession->payment_method==='PINPAYMENTS') {
    //             $OnlinePayment->status = 1;
    //             $OnlinePayment->pinpayments_card_token = $cardToken;
    //             $OnlinePayment->pinpayments_response = json_encode($response);
    //             $OnlinePayment->pinpayments_token = $response->token;
    //         } elseif ($TableorderSession->payment_method==='EWAY') {
    //             $OnlinePayment->status = 1;
    //             $OnlinePayment->eway_access_code = null;
    //             $OnlinePayment->eway_response = json_encode($EwayResponse);
    //             $OnlinePayment->eway_txn = $EwayResponse->TransactionID;
    //         } elseif ($TableorderSession->payment_method==='XENDIT') {
    //             $OnlinePayment->status = 1;
    //         } elseif ($TableorderSession->payment_method==='FISERV') {
    //             $OnlinePayment->status = 1;
    //         } elseif ($TableorderSession->payment_method==='TILLPAYMENTS') {
    //             $OnlinePayment->status = 1;
    //         }
    //         if (!$OnlinePayment->update()) {
    //             throw new Exception(implode(', ', $OnlinePayment->getMessages()), 500);
    //         }

    //         $this->db->commit();
    //     } catch(Exception $e) {
    //         $this->db->rollback();
    //         throw $e;
    //     }

    //     // Email For Customer
    //     $emailBody = 'Thank you, your payment has been received.'."<br/>";
    //     $emailBody .= 'Table Order #'.$Ticket->barcode.' ('.$Ticket->id.') $'.number_format($billAmount, 2)."<br/>";
    //     $emailBody .= $this->Branch->name.' - '.$this->Branch->phone."<br/>";

    //     if (!empty($TableorderSession->email)) {
    //         $Email = new Email();
    //         $Email->fk_user         = null;
    //         $Email->fk_device       = null;
    //         $Email->ref             = 'THANKYOU-'.$Ticket->id;
    //         $Email->from_name       = $this->Branch->name;
    //         $Email->from_address    = $this->Branch->email;
    //         $Email->reply_name      = $this->Branch->name;
    //         $Email->reply_address   = $this->Branch->email;
    //         $Email->to_name         = $TableorderSession->firstname.' '.$TableorderSession->lastname;
    //         $Email->to_address      = $TableorderSession->email;
    //         $Email->subject         = 'Thank you for your order #'.$Ticket->barcode;
    //         $Email->content         = $emailBody;
    //         $Email->priority        = 0;
    //         $Email->created         = date('Y-m-d H:i:s');
    //         if (!$Email->save()) {
    //             throw new Exception(implode($Email->getMessages()), 500);
    //         }
    //     }

    //     if (intval($this->cfg['TABLE_ORDER_DISABLE_ORDER_AFTER_PAID']??0)) {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/thankyou/'.$TableorderSession->unique_id), true);
    //     } else {
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/bill/'.$uniqueId), true);
    //     }
    // }

    // public function continueAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $ticketId = $this->ticketId;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('Please purchase first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $ticket = $this->getTicketArray($ticketId);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'service' => $service,
    //         'ticket' => $ticket,
    //     ]);
    // }

    // public function thankyouAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $ticketId = $this->ticketId;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('Please purchase first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $ticket = $this->getTicketArray($ticketId);
    //     if (!$ticket) {
    //         $this->flashSession->notice('No transaction found.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'service' => $service,
    //         'ticket' => $ticket,
    //     ]);
    // }

    // public function printBillAction($uniqueId)
    // {
    //     $legalChecker = $this->legalChecker($uniqueId);
    //     if (is_a($legalChecker, 'Phalcon\Http\Response')) {
    //         return $legalChecker;
    //     }

    //     $tableHash = $this->tableHash;
    //     $table = $this->table;
    //     $service = $this->getService();
    //     $ticketId = $this->ticketId;

    //     if (!$ticketId) {
    //         $this->flashSession->notice('Please purchase first.');
    //         return $this->response->redirect($this->url->get($this->branchSlug.'/tableorder/purchase/'.$uniqueId), true);
    //     }

    //     $TableorderSession = $this->TableorderSession;
    //     if (empty($TableorderSession->request_bill)) {
    //         $TableorderSession->request_bill = date('Y-m-d H:i:s');
    //         if (!$TableorderSession->save()) {
    //             throw new Exception(implode(', ', $TableorderSession->getMessages()), 500);
    //         }
    //     }

    //     $this->view->setVars([
    //         'uniqueId' => $uniqueId,
    //         'tableHash' => $tableHash,
    //         'table' => $table,
    //         'service' => $service,
    //     ]);
    // }
}
