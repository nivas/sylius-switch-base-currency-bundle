<?php

/**
 * bin/console nivas:switch-base-currency
 *
 * in order for this to work you need to have:
 * 1. both current base currency and new base currency in currencies admin
 * 2. exchange rate set up in sylius
 * eg:
 *  - EUR to HRK = 0,1327228084
 *  - HRK to EUR = 7,53450
 */

namespace Nivas\Bundle\SwitchBaseCurrencyBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\MoneyBundle\Formatter\MoneyFormatterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricing;
// ne postoji
// use Sylius\Component\Core\Repository\ChannelRepositoryInterface;
// use Sylius\Component\Currency\Repository\CurrencyRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Repository\ExchangeRateRepositoryInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Repository\ShippingMethodRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'nivas:switch-base-currency', description: 'Switches base currency for another currency in the system.')]
final class SwitchBaseCurrencyCommand extends Command
{
    private $entityManager; // Add the EntityManagerInterface property

    private $channelRepository;
    private $currencyRepository;
    private $exchangeRateRepository;
    private $channelPricingRepository;
    private $shippingMethodRepository;
    private $orderRepository;
    private $moneyFormatter;

    public function __construct(
        EntityManagerInterface $entityManager, // Add the EntityManagerInterface argument
        // CurrencyRepositoryInterface $currencyRepository, // nema vise
        // ChannelRepositoryInterface $channelRepository,   // nema vise
        RepositoryInterface $channelRepository,
        RepositoryInterface $currencyRepository,
        ExchangeRateRepositoryInterface $exchangeRateRepository,
        // ChannelPricing $channelPricingRepository,
        RepositoryInterface $channelPricingRepository,
        ShippingMethodRepositoryInterface $shippingMethodRepository,
        OrderRepositoryInterface $orderRepository,
        MoneyFormatterInterface $moneyFormatter,
    ) {
        $this->entityManager = $entityManager;
        $this->channelRepository = $channelRepository;
        $this->currencyRepository = $currencyRepository;
        $this->exchangeRateRepository = $exchangeRateRepository;
        $this->channelPricingRepository = $channelPricingRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->orderRepository = $orderRepository;
        $this->moneyFormatter = $moneyFormatter;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // potentially long operation ahead
        set_time_limit(0);

        $optionValue = $input->getOption('dry-run');
        if (false === $optionValue) {
            // in this case, the option was not passed when running the command
            $dry_run = false;
        } elseif (null === $optionValue) {
            // in this case, the option was passed when running the command
            // but no value was given to it
            $dry_run = true;
        } else {
            // in this case, the option was passed when running the command and
            // some specific value was given to it
            $dry_run = true;
        }

        if ($dry_run) {
            $output->writeln('<bg=yellow;options=bold>-------------------DRY RUN-------------------</>');
        }

        /**
         * let's get channel, default or user selected.
         */
        $sourceChannel = $this->getSourceChannel($input, $output);
        $sourceChannelCode = $sourceChannel->getCode();
        $output->writeln('Source channel: <info>'.$sourceChannel.'</info> ('.$sourceChannelCode.')');

        /**
         * we have channel, let's get base currency of that channel.
         */
        $sourceCurrency = $sourceChannel->getBaseCurrency();
        $sourceCurrencyCode = $sourceCurrency->getCode();
        $output->writeln('Source channel currency: <info>'.$sourceCurrency.'</info>');

        /**
         * let's get target currency.
         */
        $targetCurrency = $this->getTargetCurrency($input, $output, $sourceCurrencyCode);
        $targetCurrencyCode = $targetCurrency->getCode();
        $output->writeln('Destination currency: <info>'.$targetCurrency.'</info>');

        /**
         * we have source and destination currency, let's get exchange rate.
         */
        $exchangeRatio = $this->getExchangeRate($targetCurrencyCode, $sourceCurrencyCode); // EUR, HRK
        if (null == $exchangeRatio) {
            $output->writeln('<error>Cannot find destination currency exchange rate. Cannot continue. Exiting. <error>');

            return Command::FAILURE;
        }
        $output->writeln("Destination currency exchange ratio: <info>1 {$targetCurrencyCode} = {$exchangeRatio} {$sourceCurrencyCode} </info>");

        // do you really wish to continue
        $output->writeln('');
        $output->writeln('<error>Warning! This action will make direct changes to your database.</error>');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "\nContinue with this action (y/n)? ",
            false,
            '/^(y|j|d)/i'
        );
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        // let's go!

        // Start a new transaction
        $this->entityManager->beginTransaction();

        try {
            // set new channel base currency
            $output->writeln('Setting new base currency to the channel.... ');
            $sourceChannel->setBaseCurrency($targetCurrency);
            $this->entityManager->persist($sourceChannel);
            $this->entityManager->flush();

            // ChannelPricingInterface: Represents the sylius_channel_pricing table. You can use this interface when fetching and updating channel pricing records.
            /** @var ChannelPricingInterface[] $items */
            $items = $this->channelPricingRepository->findBy(['channelCode' => $sourceChannelCode]);
            $prices_count = count($items);
            $output->writeln("Found <info>{$prices_count}</info> product prices to modify..... ");

            foreach ($items as $item) {
                $price = (int) $item->getPrice();
                $originalPrice = (int) $item->getOriginalPrice();
                $minimumPrice = (int) $item->getMinimumPrice();

                $newPrice = self::calculate($price, (float) $exchangeRatio);
                $newOriginalPrice = self::calculate($originalPrice, (float) $exchangeRatio);
                $newMinimumPrice = self::calculate($minimumPrice, (float) $exchangeRatio);

                $product_id = $item->getId();
                $product_name = $item->getProductVariant()->getCode();

                // format the value for output from integer "123456" to "1234.56 CURRENCY" string using syilus money formatter
                $formattedPrice = $this->moneyFormatter->format($price, $sourceCurrencyCode);
                $formattedNewPrice = $this->moneyFormatter->format($newPrice, $targetCurrencyCode);
                $formattedOriginalPrice = $this->moneyFormatter->format($originalPrice, $sourceCurrencyCode);
                $formattedNewOriginalPrice = $this->moneyFormatter->format($newOriginalPrice, $targetCurrencyCode);
                $formattedMinimumPrice = $this->moneyFormatter->format($minimumPrice, $sourceCurrencyCode);
                $formattedNewMinimumPrice = $this->moneyFormatter->format($newMinimumPrice, $targetCurrencyCode);

                // $output->writeln("price: {$price} {$sourceCurrencyCode} -> {$newPrice} {$targetCurrencyCode} | originalPrice: {$originalPrice} {$sourceCurrencyCode} -> {$newOriginalPrice} {$targetCurrencyCode} | minimumPrice: {$minimumPrice} {$sourceCurrencyCode} -> {$newMinimumPrice} {$targetCurrencyCode} | $product_name ($product_id)");
                $output->writeln("price: {$formattedPrice} -> {$formattedNewPrice} | originalPrice: {$formattedOriginalPrice} -> {$formattedNewOriginalPrice} | minimumPrice: {$formattedMinimumPrice} -> {$formattedNewMinimumPrice} | {$product_name} ({$product_id})");

                $item->setPrice($newPrice);
                $item->setOriginalPrice($newOriginalPrice);
                $item->setMinimumPrice($newMinimumPrice);

                $this->entityManager->persist($item);
                // $this->entityManager->flush(); // in loop - not the most efficient way to handle the updates.
            }
            $output->writeln('<info>Done</info> modifying product prices.');

            /**
             * let's update shipping method prices.
             */
            $shippingMethods = $this->shippingMethodRepository->findAll();
            $shippingMethods_count = count($shippingMethods);
            $output->writeln("Found <info>{$shippingMethods_count}</info> shipping methods to modify..... ");

            // $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => 'hrvatska-hp']);
            foreach ($shippingMethods as $shippingMethod) {
                $configuration = $shippingMethod->getConfiguration();
                // $zone = $shippingMethod->getZone();
                // $is_enabled = $shippingMethod->isEnabled();
                // $position = $shippingMethod->getPosition();

                $shippingPrice = $configuration['default']['amount'] ?? 0;
                $newShippingPrice = self::calculate($shippingPrice, (float) $exchangeRatio);

                // Update the price in the configuration
                $configuration['default']['amount'] = $newShippingPrice;

                // Set the updated configuration back to the shipping method
                $shippingMethod->setConfiguration($configuration);

                // Persist the changes
                $this->entityManager->persist($shippingMethod);
            }
            $output->writeln('<info>Done</info> modifying shipping methods.');

            /**
             * let's clean up user carts in the system since they have old currency in database.
             */

            // Fetch all orders with a "cart" state
            $carts = $this->orderRepository->findBy(['state' => OrderInterface::STATE_CART]);
            $carts_count = count($carts);
            $output->writeln("Found <info>{$carts_count}</info> carts (orders in state 'cart') for cleanup. ");

            // Iterate through the carts and remove their items
            /** @var null|OrderInterface[] $carts */
            foreach ($carts as $cart) {
                // @var null|OrderInterface $cart

                $cart->setCurrencyCode($targetCurrencyCode);
                $cart_items_count = $cart->countItems();
                $cart_id = $cart->getId();
                $output->writeln("Reseting cart {$cart_id} totals, adjustments, items (<info>{$cart_items_count}</info>)");
                if ($cart_items_count > 0) {
                    $cart->clearItems();
                }
                $cart->recalculateItemsTotal();
                $cart->removeAdjustmentsRecursively();

                $this->entityManager->persist($cart);
            }

            $output->writeln('<info>Done</info> cleaning carts.');

            /*
             * let's wrap this up
             * flush entity and commit transaction
             */

            // Flush all changes at once
            $this->entityManager->flush();

            // Commit the transaction
            if (!$dry_run) {
                $this->entityManager->commit();
            }

            $output->writeln('<info>Currency switch done.</info>');
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            $this->entityManager->rollback();

            $output->writeln(sprintf('<error>An error occurred: %s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to switch base currency for another currency in the systen.')
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Dry run, don\'t make any changes to database.',
                false
            )
        ;
    }

    private function getTargetCurrency(InputInterface $input, OutputInterface $output, string $sourceCurrencyCode): CurrencyInterface
    {
        /**
         * let's get source and destination currency
         * we take all currencies and remove base one from the list.
         */

        /** @var CurrencyInterface[] $currencies */
        $currencies = $this->currencyRepository->findAll();
        foreach ($currencies as $key => $currency) {
            if ($sourceCurrencyCode == $currency->getCode()) {
                unset($currencies[$key]);
            }
        }

        if (count($currencies) > 1) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select destination currency:',
                $currencies,
                0
            );
            $question->setErrorMessage('Currency %s is invalid.');
            $targetCurrency = $helper->ask($input, $output, $question);
        } else {
            $targetCurrency = reset($currencies);
        }

        return $targetCurrency;
    }

    private function getSourceChannel(InputInterface $input, OutputInterface $output): ChannelInterface
    {
        /** @var ChannelInterface[] $channels */
        $channels = $this->channelRepository->findAll();

        if (count($channels) > 1) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select channel:',
                $channels, // choices can also be PHP objects that implement __toString() method
                0
            );
            $question->setErrorMessage('Channel %s is invalid.');

            /** @var ChannelInterface $sourceChannel */
            $sourceChannel = $helper->ask($input, $output, $question);
        } else {
            /** @var ChannelInterface $sourceChannel */
            $sourceChannel = reset($channels);
        }

        return $sourceChannel;
    }

    private function getExchangeRate($sourceCurrencyCode, $targetCurrencyCode): null|float
    {
        $exchangeRates = $this->exchangeRateRepository->findAll();

        // Iterate through the exchange rates and perform your desired actions
        foreach ($exchangeRates as $exchangeRate) {
            // Access properties and methods of the ExchangeRateInterface
            $sourceCurrency = $exchangeRate->getSourceCurrency();
            $targetCurrency = $exchangeRate->getTargetCurrency();
            $ratio = $exchangeRate->getRatio();

            // check and return ratio if source and destination currency codes match
            if ($sourceCurrencyCode == $sourceCurrency->getCode()
            && $targetCurrencyCode == $targetCurrency->getCode()) {
                // ako ne tocno unesen takav exchange
                return $ratio;
            }
            if ($targetCurrencyCode == $sourceCurrency->getCode()
            && $sourceCurrencyCode == $targetCurrency->getCode()) {
                // ako su uneseni ali OBRNUTO onda reversaj
                return 1 / $ratio;
            }
        }

        return null;
    }

    /**
     *  190000 / 100 = 1.900
     *  1900 / 7,53450 <- zaokruziti rezultat = 252,17
     *  252,17 * 100 <- da se maknu decimale = 25.217
     *  =25217.
     *
     * PHP_ROUND_HALF_UP - Rounds num away from zero when it is half way there, making 1.5 into 2 and -1.5 into -2.
     */
    private static function calculate(int $sourcePrice, float $exchangeRate): int
    {
        if (0 == $sourcePrice) {
            return 0;
        }

        $price = $sourcePrice / 100;
        $price = round($price / $exchangeRate, 2, PHP_ROUND_HALF_UP);

        return $price * 100;
    }
}
