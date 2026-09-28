package ga.dgcpt.plateforme;

import android.app.DownloadManager;
import android.content.Context;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.webkit.CookieManager;
import android.webkit.URLUtil;
import android.widget.Toast;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        String currentUserAgent = bridge.getWebView().getSettings().getUserAgentString();
        if (currentUserAgent == null || !currentUserAgent.contains("DGCPT-Android/")) {
            bridge.getWebView().getSettings().setUserAgentString(
                (currentUserAgent == null ? "" : currentUserAgent) + " DGCPT-Android/1.1"
            );
        }

        bridge.getWebView().setDownloadListener((url, userAgent, contentDisposition, mimeType, contentLength) -> {
            try {
                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                request.setMimeType(mimeType);
                request.addRequestHeader("User-Agent", userAgent);

                String cookies = CookieManager.getInstance().getCookie(url);
                if (cookies != null && !cookies.isBlank()) {
                    request.addRequestHeader("Cookie", cookies);
                }

                String filename = URLUtil.guessFileName(url, contentDisposition, mimeType);
                request.setTitle(filename);
                request.setDescription(getString(R.string.download_description));
                request.setNotificationVisibility(
                    DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
                );
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, filename);

                DownloadManager manager =
                    (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                manager.enqueue(request);
                Toast.makeText(this, R.string.download_started, Toast.LENGTH_SHORT).show();
            } catch (Exception exception) {
                Toast.makeText(this, R.string.download_failed, Toast.LENGTH_LONG).show();
            }
        });
    }
}
