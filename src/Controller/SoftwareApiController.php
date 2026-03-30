<?php

namespace App\Controller;

use App\Repository\SoftwareVersionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SoftwareApiController extends AbstractController
{
    #[Route('/api/carplay/software/version', name: 'api_software_version', methods: ['POST'])]
    public function softwareDownload(Request $request, SoftwareVersionRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $data = $request->request->all();
        }

        $version = $data['version'] ?? '';
        $hwVersion = $data['hwVersion'] ?? '';

        if (empty($version)) {
            return $this->json(['msg' => 'Version is required']);
        }

        if (empty($hwVersion)) {
            return $this->json(['msg' => 'HW Version is required']);
        }

        $patternST = '/^CPAA_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';
        $patternGD = '/^CPAA_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';

        $patternLCI_CIC = '/^B_C_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
        $patternLCI_NBT = '/^B_N_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
        $patternLCI_EVO = '/^B_E_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

        $hwVersionBool = false;
        $stBool = false;
        $gdBool = false;
        $isLCI = false;
        $lciHwType = '';

        if (preg_match($patternST, $hwVersion)) {
            $hwVersionBool = true;
            $stBool = true;
        }

        if (preg_match($patternGD, $hwVersion)) {
            $hwVersionBool = true;
            $gdBool = true;
        }

        if (preg_match($patternLCI_CIC, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'CIC';
            $stBool = true;
        } elseif (preg_match($patternLCI_NBT, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'NBT';
            $gdBool = true;
        } elseif (preg_match($patternLCI_EVO, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'EVO';
            $gdBool = true;
        }

        if (!$hwVersionBool) {
            return $this->json(['msg' => 'There was a problem identifying your software. Contact us for help.']);
        }

        if (str_starts_with($version, 'v') || str_starts_with($version, 'V')) {
            $version = substr($version, 1);
        }

        $candidates = $repository->findBySystemVersionAlt($version);

        foreach ($candidates as $row) {
            $isLCIEntry = str_starts_with($row->getName(), 'LCI');

            if ($isLCI !== $isLCIEntry) {
                continue;
            }

            if ($isLCI && stripos($row->getName(), $lciHwType) === false) {
                continue;
            }

            if ($row->isLatest()) {
                return $this->json([
                    'versionExist' => true,
                    'msg' => 'Your system is upto date!',
                    'link' => '',
                    'st' => '',
                    'gd' => '',
                ]);
            }

            $stLink = '';
            $gdLink = '';
            if ($stBool) {
                $stLink = $row->getStLink() ?? '';
            }
            if ($gdBool) {
                $gdLink = $row->getGdLink() ?? '';
            }

            $latestVersion = $repository->findLatestForCategory($isLCI);
            $latestMsg = $latestVersion ? $latestVersion->getSystemVersion() : ($isLCI ? 'v3.4.4' : 'v3.3.7');

            return $this->json([
                'versionExist' => true,
                'msg' => 'The latest version of software is ' . $latestMsg . ' ',
                'link' => $row->getLink() ?? '',
                'st' => $stLink,
                'gd' => $gdLink,
            ]);
        }

        return $this->json([
            'versionExist' => false,
            'msg' => 'There was a problem identifying your software. Contact us for help.',
            'link' => '',
            'st' => '',
            'gd' => '',
        ]);
    }
}
