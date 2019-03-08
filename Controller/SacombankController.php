<?php
namespace Plugin\Sacombank\Controller;

use Plugin\Sacombank\Repository\PaidLogsRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Controller\AbstractController;
use Eccube\Service\CartService;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\OrderStateMachine;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;

class SacombankController extends AbstractController
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var PaidLogsRepository
     */
    protected $paidLogsRepository;

    /**
     * SacombankController constructor.
     *
     * @param CartService $cartService
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param OrderStateMachine $orderStateMachine
     * @param PaidLogsRepository $paidLogsRepository
     */
    public function __construct(
        CartService $cartService,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        OrderStateMachine $orderStateMachine,
        PaidLogsRepository $paidLogsRepository
    ) {
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->paidLogsRepository = $paidLogsRepository;
    }

    /**
     * @Route("/sacombank/cancel", name="sacombank_back", methods={"POST", "GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cancelled(Request $request)
    {
        $this->addError('Bạn đã hủy thanh toán');
        return $this->redirectToRoute('shopping_error');
    }

    /**
     * @Route("/sacombank/complete", name="sacombank_complete")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function complete(Request $request)
    {
        $preOrderId =  $request->get('req_transaction_uuid');
        $Order = $this->orderRepository->findOneBy(['pre_order_id' => $preOrderId]);
        if (!$Order instanceof Order) {
            throw new NotFoundHttpException();
        }

        $this->paidLogsRepository->saveLogs($Order, $_POST);

        if ($this->getUser() != $Order->getCustomer()) {
            throw new NotFoundHttpException();
        }

        $PaymentMethod = $this->container->get($Order->getPayment()->getMethodClass());

        $result = $PaymentMethod->handleRequest($request);
        if ($result['status'] === 'success') {
            $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::NEW));
            $Order->setOrderDate(new \DateTime());

            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                $this->orderStateMachine->apply($Order, $OrderStatus);
                $Order->setPaymentDate(new \DateTime());
            }

            $this->cartService->clear();

            $this->session->set('eccube.front.shopping.order.id', $Order->getId());
            $this->entityManager->flush();
            return $this->redirectToRoute('shopping_complete');
        } else {
            // TODO: message
            $this->addError($result['message']);
            return $this->redirectToRoute('shopping_error');
        }
    }
}