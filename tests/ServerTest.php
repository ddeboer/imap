<?php

declare(strict_types=1);

namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\ConnectionInterface;
use Ddeboer\Imap\Exception\AuthenticationFailedException;
use Ddeboer\Imap\Server;

/**
 * @covers \Ddeboer\Imap\Server
 */
final class ServerTest extends AbstractTest
{
    private $hostname;
    private $port;
    private $flags;
    private $parameters;

    protected function setUp()
    {
        $this->hostname = 'dummy-imap-server.example.com';
        $this->port = '5555'; // TODO: Should't the port be numeric? http://php.net/manual/en/function.imap-open.php states that the port is a number.
        $this->flags = ($this->flagsProvider())['properly formatted'][0];
        $this->parameters = ['a' => 'b'];
    }

    public function testValidConnection()
    {
        $connection = $this->getConnection();

        $check = \imap_check($connection->getResource()->getStream());

        $this->assertInstanceOf(\stdClass::class, $check);
    }

    public function testFailedAuthenticate()
    {
        $server = new Server(\getenv('IMAP_SERVER_NAME'), \getenv('IMAP_SERVER_PORT'), self::IMAP_FLAGS);

        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessageRegExp('/E_WARNING.+AUTHENTICATIONFAILED/s');

        $server->authenticate(\uniqid('fake_username_'), \uniqid('fake_password_'));
    }

    public function testEmptyPort()
    {
        if ('933' !== \getenv('IMAP_SERVER_PORT')) {
            $this->markTestSkipped('Active IMAP test server must have 993 port for this test');
        }

        $server = new Server(\getenv('IMAP_SERVER_NAME'), '', self::IMAP_FLAGS);

        $this->assertInstanceOf(ConnectionInterface::class, $server->authenticate(\getenv('IMAP_USERNAME'), \getenv('IMAP_PASSWORD')));
    }

    public function testGetHostname()
    {
        $server = new Server($this->hostname);

        $this->assertSame($this->hostname, $server->getHostname());
    }

    public function testGetPortReturnsConstructorPortWhenPortIsStated()
    {
        $server = new Server($this->hostname, $this->port);

        $this->assertSame($this->port, $server->getPort());
    }

    public function testGetPortReturnsDefaultPortWhenPortIsNotStated()
    {
        $server = new Server($this->hostname);

        $this->assertSame($server::DEFAULT_PORT, $server->getPort());
    }

    /** @dataProvider flagsProvider */
    public function testGetFlagsReturnsConstructorFlagsWhenFlagsAreStated(string $flags, string $expectedFlags)
    {
        $server = new Server($this->hostname, $this->port, $flags);

        $this->assertSame($expectedFlags, $server->getFlags());
    }

    public function testGetFlagsReturnsDefaultFlagsWhenFlagsAreNotStated()
    {
        $server = new Server($this->hostname);

        $this->assertSame($server::DEFAULT_FLAGS, $server->getFlags());
    }

    public function testGetParametersReturnsConstructorParamatersWhenParametersAreStated()
    {
        $server = new Server($this->hostname, $this->port, $this->flags, $this->parameters);

        $this->assertSame($this->parameters, $server->getParameters());
    }

    public function testGetParametersReturnsEmptyArrayWhenParametersAreNotStated()
    {
        $server = new Server($this->hostname);

        $this->assertCount(0, $server->getParameters());
    }

    // Data providers

    public function flagsProvider(): array
    {
        return [
            'properly formatted' => ['/silly/flags', '/silly/flags'],
            'without leading slash' => ['silly/flags', '/silly/flags'],
            'single slash' => ['/', '/'],
            'empty string' => ['', ''],
        ];
    }
}
