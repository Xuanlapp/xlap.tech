(() => {
  const WEB_SOURCE = "ETSY_CRAWLER_WEB_BRIDGE";
  const RESPONSE_SOURCE = "ETSY_CRAWLER_EXTENSION_RESPONSE";
  const READY_SOURCE = "ETSY_CRAWLER_EXTENSION_BRIDGE";

  function postReady() {
    window.postMessage({
      source: READY_SOURCE,
      type: "ETSY_BRIDGE_READY",
    }, window.location.origin);
  }

  window.addEventListener("message", (event) => {
    if (event.source !== window || event.data?.source !== WEB_SOURCE) {
      return;
    }

    if (event.data?.type === "ETSY_BRIDGE_PING") {
      postReady();
      return;
    }

    const messageId = event.data?.messageId;
    const message = event.data?.message;

    if (!messageId || !message) {
      return;
    }

    chrome.runtime.sendMessage(message, (response) => {
      const error = chrome.runtime.lastError?.message || null;

      window.postMessage({
        source: RESPONSE_SOURCE,
        messageId,
        response,
        error,
      }, window.location.origin);
    });
  });

  postReady();
})();
