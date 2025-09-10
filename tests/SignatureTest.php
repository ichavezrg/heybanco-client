<?php

namespace Ichavez\HeyBancoClient\Tests;

use Ichavez\HeyBancoClient\Signature;
use PHPUnit\Framework\TestCase;

class SignatureTest extends TestCase
{
    public function testSignature()
    {
        $signature = new Signature(
            bApplication: '845b7687-3886-4bb4-be1c-33e45a6c3d34',
            pemPrivateKeyPath: 'tests/Client_private_key_in_pem.pem',
            pemServerPublicKeyPath: 'tests/Server_PublicKey_JWE.pem'
        );

        $payload = [
            'test' => 'test'
        ];

        $signedPayload = $signature->sign($payload);

        $this->assertNotEmpty($signedPayload);
    }

    // public function testDecryption()
    // {
    //     $signature = new Signature(
    //         bApplication: '845b7687-3886-4bb4-be1c-33e45a6c3d34',
    //         pemPrivateKeyPath: 'tests/Client_private_key_in_pem.pem',
    //         pemServerPublicKeyPath: 'tests/Server_PublicKey_JWE.pem'
    //     );

    //     $payload = $signature->decrypt("eyJhbGciOiJSU0EtT0FFUC0yNTYiLCJlbmMiOiJBMjU2R0NNIiwia2lkIjoiODQ1Yjc2ODctMzg4Ni00YmI0LWJlMWMtMzNlNDVhNmMzZDM0In0.IZXlFk4vPk2glMMDFOuWpOu5uigVxzxbRAxgZW91TvVUBjRWmoTNp8EpLY0v5DM3lLg7WK-uOW0oQT4tznWDqRB85JtVwMrrRFlHaewxDGJkIbmygi5fWzQGUcpSUmMuubQ48tAXBOLW1mrJL3IcZrHbNR00r3fxrwKxxTGDvnZovgprD-4PvKvDzywJ2gkLWhNjH4o7PivbMHN3Ad1lkOoQwuj80xoU0-XbbGuTrGZdrmNxdhef0A7AuLf08ZBC6vzK5bxw4x7QzZTGtRU5TTKtYTxJ2WiOuE3hURgMBIKfuv9vg21hjymGivLtdHlzpEFr6HzuEAyA2quDShFEor9GtRb-8CshjcHhpDOrCdsQqWfJcxj20-1Ezl0SpgyRa28eJqnOXzaBbcDSugfbgq08tDd7Sd1rZMRbLm3yhGzTTuW0YbQ2JrcIbDaJRQXNT7GdS-BevU-PlcqyjorpJdp_6Rr7pcMcqRjH5Il7ZKJDuKtp6KOioCMz3YxE9gP4lM9skzwNZe9gTEojKgO177zS5KQCwESIYyzRW5vqP6xOHkMTs_Hs-_VM8vTjYUOpUICkKNnYUHX6OMd9p9gA0bTSXOqF0xbDvG9p5wcatf5bGclbQvcND9X63w6S-djbB2qN8eY0jw_lnrDbwyXjjjMka-IeAuKvdbXQ1W6ZphY.SQltIDcbf0Wui6k2.L2tWXj0yUXLjDUFe3SaURqovoeItQE2SvGAaJi96QMg9l3-vahBL40SxLwC6kc9ymcgiXWgkaHQd9nBPxS0r3Lzv3IQ0q1ZjBvo_n4M-i88L7ZEEc-lTnRWRtxDsMijRjUvtYa72wztLI3BN3tlsrJKBnQzsCoCAxDkI_0FU6jXaDQNFD5gGCgTVicrWqhPnLUtEH7atInq4VOFRq8UGlSWfrqmos2Q5d8JqwdwoZW6BMjSgDidRHNh3OotRB7zEXg_rYsBLikPUZDyJh7F2JF5gDBohn4xbluqg6wJPogjQ5n5v8nENw6u0G7Mzm9kddAZoq1CNjlWYAFOUMwSpD74iZnHzKa6N4ODp2HGoPvG89rl8OPxg8anbx-qOE_PJM2HRjlWtjX149fAsNvBOg6jGdLYHS-PJFcuRQdxaxnO5pBRCSEejvhhSi9LmNZRyFNbd06cBlsn-RAjmbmTqncm48mEDzA9Bm9rG0kczaBADUuKANUdDAhqBsaNFH_h9ds9Rq_CiWvkHi7MbR5S7fZUlnZymr3tGW2ZKYWU-cc2-CURjJ7NKPDo92ban5oLdR3r8KEUYjZkT1fwDP16C9YE9hcI92LjoHVdO4xQldv_9JHCxP4UmE6Fw0L2uHJAHIV74ahbfHTSu6d958R8-M3vQ2IkqjslYZM1Liu4AIYTUUITxsf7YjLD3hDMlAOcPQEHjnIydrNylKWbKC0XdtkQAU67sHCnoHGmBX6ho7a0insySj2bbcqOBjZ9uIjgjQ5QLkCTHbEU64ZzfbtRtO9gmgK1qFnni17eLPHFJV4z0mxazncxUypemv_0j00jDG98HQb1vaeUNMwHo5Oa34tJCGuSIPvHacDNkWyZTFKXaiqf5u0WhE2WTN1ExFCQhH0_woA0GDxOTVSTxzrEvmqS5JwY8-Jqy2nYSxIG9J_2nJNhl1qfeF3jbRekR3P-pXPdmUyG77X48ESRT7um_hxWIX8Pp1x5nPAaKg2_BnUXXhHR_nNzMxFN0DYW70SkmyevyL3lwSvEkdG8cfaqsizo.KC1L2I2gr5H45ZIXqKuAhQ");
    //     print_r($payload);
    // }
}
