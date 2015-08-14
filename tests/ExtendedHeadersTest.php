<?php

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Message\ExtendedHeaders;

class ExtendedHeadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing wrong encoding in header
     *
     **/
    public function testParse()
    {
        $header =<<<TXT
Delivered-To: user@gmail.com
Received: by 10.42.176.5 with SMTP id bc5cs19805icb;
        Fri, 15 Jul 2011 07:58:04 -0700 (PDT)
Received: by 10.204.240.144 with SMTP id la16mr1280644bkb.271.1310741882850;
        Fri, 15 Jul 2011 07:58:02 -0700 (PDT)
Return-Path: <techsup@tourout.ru>
Received: from t.pdg.ru (static.14.80.4.46.clients.your-server.de [46.4.80.14])
        by mx.google.com with ESMTPS id c13si1665775fai.198.2011.07.15.07.58.02
        (version=TLSv1/SSLv3 cipher=OTHER);
        Fri, 15 Jul 2011 07:58:02 -0700 (PDT)
Received-SPF: pass (google.com: domain of techsup@tourout.ru designates 46.4.80.14 as permitted sender) client-ip=46.4.80.14;
Authentication-Results: mx.google.com; spf=pass (google.com: domain of techsup@tourout.ru designates 46.4.80.14 as permitted sender) smtp.mail=techsup@tourout.ru
Received: from www-data by t.pdg.ru with local (Exim 4.72)
    (envelope-from <techsup@tourout.ru>)
    id 1Qhjq6-0007D5-9q
    for Linasgreen@gmail.com; Fri, 15 Jul 2011 18:58:02 +0400
To: user2@gmail.com
Subject: ,  ����� ������ ��������� TourOut.ru � ������ ����!
X-PHP-Originating-Script: 33:Email.php
User-Agent: PDG.ru mail sender
Date: Fri, 15 Jul 2011 18:58:02 +0400
From: "������������� TourOut.ru" <techsup@tourout.ru>
Reply-To: "techsup@tourout.ru" <techsup@tourout.ru>
X-Sender: techsup@tourout.ru
X-Mailer: PDG.ru mail sender
X-Priority: 3 (Normal)
Message-ID: <4e20557a49160@tourout.ru>
Mime-Version: 1.0
Content-Type: multipart/alternative; boundary="B_ALT_4e20557a491ae"
TXT;

        $result =  ExtendedHeaders::parse($header);
        //var_export($result);
        $i = 0;
        foreach($result as $item ){
            if($item['name'] == 'Message-ID'){
                $i++;
                $this->assertEquals('<4e20557a49160@tourout.ru>',$item['value']);
            }

            if($item['name'] == 'To'){
                $i++;
                $this->assertEquals('user2@gmail.com',$item['value']);
            }
        }

        $this->assertEquals(2,$i);
    }
}
