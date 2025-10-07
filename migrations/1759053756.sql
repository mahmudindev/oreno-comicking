--
-- UP
--

CREATE TABLE public.app_messenger_messages (
    id                 bigserial                   PRIMARY KEY,
    created_at         timestamp without time zone NOT NULL,

    body               text                        NOT NULL,
    headers            text                        NOT NULL,
    queue_name         varchar(190)                NOT NULL,
    available_at       timestamp without time zone NOT NULL,
    delivered_at       timestamp without time zone DEFAULT NULL
);

CREATE INDEX app_messenger_messages_queue_name_idx ON public.app_messenger_messages (queue_name);
CREATE INDEX app_messenger_messages_available_at_idx ON public.app_messenger_messages (available_at);
CREATE INDEX app_messenger_messages_delivered_at_idx ON public.app_messenger_messages (delivered_at);

CREATE FUNCTION public.app_messenger_messages_notify() RETURNS TRIGGER AS $$
    BEGIN
        PERFORM pg_notify('app_messenger_messages', NEW.queue_name :: text);
        RETURN NEW;
    END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER app_messenger_messages_notify_trigger
    AFTER INSERT OR UPDATE ON public.app_messenger_messages
    FOR EACH ROW EXECUTE PROCEDURE app_messenger_messages_notify();

--
-- DOWN
--

DROP FUNCTION public.app_messenger_messages_notify();
DROP TABLE public.app_messenger_messages;
