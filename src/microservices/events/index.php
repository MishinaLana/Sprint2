<?php

$url = $_SERVER['PATH_INFO'];
if (false === preg_match('/\/(?<topicName>[^\/]+)$/', $url, $parseUrlResult)) {
    http_response_code(404);
    echo 'Url is not correct';

    die();
}

if ($parseUrlResult['topicName'] === 'health') {
    http_response_code(200);

    echo json_encode(['status' => true]);

    die();
}

$topics = ['movie-events', 'user-events', 'payment-events'];
$topic = $parseUrlResult['topicName'] . '-events';

if (isset($_GET['consume'])) {
    consumeTopic($topic);
} else {
    produceTopic($topic, ['message_id' => random_bytes(10), 'message' => sprintf('new message for %s', $topic)]);

    http_response_code(201);

    echo json_encode(['status' => 'success']);
}


function produceTopic(string $topic, array $message): void
{
    $conf = new RdKafka\Conf();
    $conf->set('log_level', (string) LOG_DEBUG);
    $conf->set('debug', 'all');
    $conf->set('metadata.broker.list', getenv('KAFKA_BROKERS'));
    $rk = new RdKafka\Producer($conf);

    $topic = $rk->newTopic($topic);

    $topic->produce(RD_KAFKA_PARTITION_UA, 0, serialize($message));
    $rk->poll(0);
    $rk->flush(10000);
}

function consumeTopic(string $topic): void
{
    $conf = new RdKafka\Conf();
    $conf->set('log_level', (string) LOG_DEBUG);
    $conf->set('debug', 'all');
    $conf->set('metadata.broker.list', getenv('KAFKA_BROKERS'));
    $conf->set('group.id', 'myConsumerGroup');

// Emit EOF event when reaching the end of a partition
    $conf->set('enable.partition.eof', 'true');
    $rk = new RdKafka\Consumer($conf);

    $topicConf = new RdKafka\TopicConf();
    $topicConf->set('auto.commit.interval.ms', 100);

    $topicConf->set('offset.store.method', 'broker');
    $topicConf->set('auto.offset.reset', 'earliest');

    $topic = $rk->newTopic($topic, $topicConf);
    $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        $message = $topic->consume(0, 120*10000);
        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                var_dump($message);
                break;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                echo "No more messages; will wait for more\n";
                break;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                echo "Timed out\n";
                break;
            default:
                throw new \Exception($message->errstr(), $message->err);
                break;
        }

        var_dump($message);
}
