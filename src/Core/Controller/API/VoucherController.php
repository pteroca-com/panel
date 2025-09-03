<?php

namespace App\Core\Controller\API;

use App\Core\Attribute\RequiresVerifiedEmail;
use App\Core\Service\Voucher\VoucherService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VoucherController extends APIAbstractController
{
    #[Route('/panel/api/voucher/redeem', name: 'api_voucher_redeem', methods: ['POST'])]
    #[RequiresVerifiedEmail]
    public function redeemVoucher(
        Request $request,
        VoucherService $voucherService,
    ): JsonResponse
    {
        $voucherCode = $request->request->getString('code');
        $orderAmount = (float)$request->request->getString('amount');
        $voucherRedeemResult = $voucherService->redeemVoucher($voucherCode, $orderAmount, $this->getUser());

        return new JsonResponse(
            $voucherRedeemResult->toArray(),
            $voucherRedeemResult->isSuccess() ? 200 : 400,
        );
    }
}
