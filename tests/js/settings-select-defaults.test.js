import { describe, expect, it } from 'vitest';
import settingsForm from '@/mixins/settings-form';

/**
 * Regression: a select bound to null matches no option - not the one whose
 * value is the empty string - so the rating limits rendered blank on any
 * install that had never set one, and the field looked broken. Nothing about
 * what is stored changes; null and '' both mean no limit.
 *
 * This lives on the mixin both settings screens use, so it is called directly
 * rather than through whichever screen happens to render the select today.
 */
function settings() {
    return { vm: settingsForm.methods };
}

describe('settings that reach a select', () => {
    it('turns a null rating limit into the None option', () => {
        const filled = settings().vm.withSelectDefaults({ mpaa_limit: null, tv_limit: null });

        expect(filled.mpaa_limit).toBe('');
        expect(filled.tv_limit).toBe('');
    });

    it('turns a missing rating limit into the None option', () => {
        const filled = settings().vm.withSelectDefaults({});

        expect(filled.mpaa_limit).toBe('');
        expect(filled.tv_limit).toBe('');
    });

    it('leaves a real rating limit alone', () => {
        const filled = settings().vm.withSelectDefaults({ mpaa_limit: 'PG-13', tv_limit: 'TV-14' });

        expect(filled.mpaa_limit).toBe('PG-13');
        expect(filled.tv_limit).toBe('TV-14');
    });

    it('defaults the other selects rather than letting them render blank', () => {
        const filled = settings().vm.withSelectDefaults({});

        expect(filled.theater_name_position).toBe('bottom');
        expect(filled.poster_fill_scrim).toBe('standard');
        expect(filled.transition_type).toBe('fade');
    });

    it('leaves those alone when they are already set', () => {
        const filled = settings().vm.withSelectDefaults({
            theater_name_position: 'top',
            poster_fill_scrim: 'none',
            transition_type: 'crossfade',
        });

        expect(filled.theater_name_position).toBe('top');
        expect(filled.poster_fill_scrim).toBe('none');
        expect(filled.transition_type).toBe('crossfade');
    });
});
