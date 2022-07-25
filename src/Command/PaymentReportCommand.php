<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Service\Twig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentReportCommand extends Command
{
    private Twig $twig;
    private MailerInterface $mailer;
    private TransactionRepository $transactionRepository;

    protected static $defaultName = 'payment:report';
    public function __construct(
        Twig $twig,
        MailerInterface $mailer,
        TransactionRepository $transactionRepository,
        string $name = null
    ) {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->transactionRepository = $transactionRepository;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transactions = $this->transactionRepository->getPayStatisticPerMonth();

        if ($transactions) {
            $startDate = (new \DateTime())->modify('-1 month')->format('d.m.Y');
            $endDate = (new \DateTime())->format('d.m.Y');


            $total = array_sum(array_column($transactions, 'total_amount'));

            $reportTemplate = $this->twig->render(
                'mail/paymentReport.html.twig',
                [
                    'transactions' => $transactions,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'total' => $total
                ]
            );

            $mail = (new Email())
                ->to($_ENV['REPORT_EMAIL'])
                ->from('admin@mail.ru')
                ->subject('Отчет об оплаченных курсах')
                ->html($reportTemplate);

            try {
                $this->mailer->send($mail);
            } catch (TransportException $exception) {
                $output->writeln($exception->getMessage());
                $output->writeln('Ошибка при отправке отчета');

                return Command::FAILURE;
            }
        }

        $output->writeln('Отчет успешно отправлен!');
        return Command::SUCCESS;
    }
}
