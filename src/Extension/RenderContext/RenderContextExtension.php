<?php

namespace League\Plates\Extension\RenderContext;

use League\Plates;

/** The render context extension provides a RenderContext object and functions to be used within the render context object */
final class RenderContextExtension implements Plates\Extension
{
    public function register(Plates\Engine $plates) {
        $c = $plates->getContainer();
        $c->add('renderContext.func', function($c) {
            return Plates\Util\stack($c->get('renderContext.func.stack'));
        });
        $c->add('renderContext.func.stack', function($c) {
            return [
                'plates' => Plates\Util\stackGroup([
                    aliasNameFunc($c->get('renderContext.func.aliases')),
                    splitByNameFunc($c->get('renderContext.func.funcs'))
                ]),
                'notFound' => notFoundFunc(),
            ];
        });
        $c->add('renderContext.func.aliases', [
            'e' => 'escape',
            '__invoke' => 'escape',
            'stop' => 'end',
        ]);
        $c->add('renderContext.func.funcs', function($c) {
            $template_args = assertTemplateArgsFunc();
            $one_arg = assertArgsFunc(1);
            $config = $c->get('config');

            return [
                'insert' => [$template_args, insertFunc()],
                'escape' => [
                    $one_arg,
                    isset($config['escape_flags'], $config['escape_encoding'])
                        ? escapeFunc($config['escape_flags'], $config['escape_encoding'])
                        : escapeFunc()
                ],
                'data' => [accessTemplatePropFunc('data')],
                'name' => [accessTemplatePropFunc('name')],
                'context' => [accessTemplatePropFunc('context')],
                'component' => [$template_args, componentFunc()],
                'slot' => [$one_arg, slotFunc()],
                'end' => [endFunc()]
            ];
        });

        $c->wrap('compose', function($compose, $c) {
            return Plates\Util\compose($compose, renderContextCompose(
                $c->get('renderContext.factory'),
                $c->get('config')['render_context_var_name']
            ));
        });
        $c->add('include.bind', function($c) {
            return renderContextBind($c->get('config')['render_context_var_name']);
        });
        $c->add('renderContext.factory', function($c) {
            return RenderContext::factory(
                function() use ($c) { return $c->get('renderTemplate'); },
                $c->get('renderContext.func')
            );
        });
    }
}