import { extend } from 'flarum/extend';
import app from 'flarum/app';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';

app.initializers.add('flarum-auth-cas', () => {
  extend(LogInButtons.prototype, 'items', function(items) {
    items.add('cas',
      <LogInButton
        className="Button LogInButton--cas"
        icon="lock"
        path="/auth/cas">
        {app.translator.trans('通过 CAS 登录/注册')}
      </LogInButton>
    );
  });
});
